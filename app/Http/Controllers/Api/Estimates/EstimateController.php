<?php

namespace App\Http\Controllers\Api\Estimates;

use App\Domain\Estimates\Models\Estimate;
use App\Domain\Estimates\Models\EstimateItem;
use App\Domain\Estimates\Models\EstimateItemSource;
use App\Domain\CRM\Models\Counterparty;
use App\Http\Controllers\Controller;
use App\Http\Requests\Estimates\EstimateStoreRequest;
use App\Http\Requests\Estimates\EstimateUpdateRequest;
use App\Http\Resources\EstimateResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EstimateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = $perPage <= 0 ? 10 : min($perPage, 200);
        $page = (int) $request->integer('page', 1);

        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = Estimate::query()
            ->with(['counterparty', 'creator', 'contract:id,estimate_id'])
            ->withCount('items')
            ->withSum('items as total_sum', 'total')
            ->orderByDesc('id');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                    ->orWhere('client_phone', 'like', "%{$search}%")
                    ->orWhere('site_address', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        $estimates = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => collect($estimates->items())->map(
                fn (Estimate $estimate) => (new EstimateResource($estimate))->toArray($request),
            ),
            'meta' => [
                'current_page' => $estimates->currentPage(),
                'per_page' => $estimates->perPage(),
                'total' => $estimates->total(),
                'last_page' => $estimates->lastPage(),
            ],
        ]);
    }

    public function store(EstimateStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        do {
            $randomId = Str::random(12);
        } while (Estimate::query()->where('random_id', $randomId)->exists());
        $data = $request->validated();
        $isDraft = $request->boolean('draft');
        $counterpartyId = $isDraft
            ? ($data['client_id'] ?? null)
            : $this->resolveCounterpartyId($data, $tenantId, $companyId, $request->user()?->id);

        $estimate = Estimate::query()->create([
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            'client_id' => $counterpartyId,
            'client_name' => $data['client_name'] ?? null,
            'client_phone' => $data['client_phone'] ?? null,
            'site_address' => $data['site_address'] ?? null,
            'data' => [],
            'random_id' => $randomId,
            'link' => "/estimate/{$randomId}",
            'link_montaj' => "/estimate/{$randomId}mnt",
            'public_expires_at' => now()->addDays(30),
            'sms_sent' => 0,
            'created_by' => $user?->id,
            'updated_by' => $user?->id,
        ]);

        $estimate->load('counterparty');
        $estimate->loadCount('items')->loadSum('items as total_sum', 'total');

        return response()->json([
            'data' => (new EstimateResource($estimate))->toArray($request),
        ]);
    }

    public function show(Request $request, int $estimate): JsonResponse
    {
        $model = $this->resolveEstimate($request, $estimate);

        $model->load([
            'items.product',
            'items.product.unit',
            'items.group',
            'counterparty',
            'contract:id,estimate_id',
        ]);
        $model->load('counterparty');
        $model->loadCount('items')->loadSum('items as total_sum', 'total');

        return response()->json([
            'data' => (new EstimateResource($model))->toArray($request),
        ]);
    }

    public function update(EstimateUpdateRequest $request, int $estimate): JsonResponse
    {
        $model = $this->resolveEstimate($request, $estimate);
        $data = $request->validated();
        $isDraft = $request->boolean('draft');
        unset($data['draft']);

        if (!$isDraft && !$model->client_id) {
            $data['client_id'] = $this->resolveCounterpartyId($data, $model->tenant_id, $model->company_id, $request->user()?->id);
        } elseif (!array_key_exists('client_id', $data) && $model->client_id) {
            $data['client_id'] = $model->client_id;
        }

        if ($request->user()) {
            $data['updated_by'] = $request->user()->id;
        }

        $model->fill($data);
        $model->save();
        $model->loadCount('items')->loadSum('items as total_sum', 'total');

        return response()->json([
            'data' => (new EstimateResource($model))->toArray($request),
        ]);
    }

    public function destroy(Request $request, int $estimate): JsonResponse
    {
        $model = $this->resolveEstimate($request, $estimate);
        $hasContracts = false;

        if (Schema::connection('legacy_new')->hasColumn('contracts', 'estimate_id')) {
            $hasContracts = DB::connection('legacy_new')
                ->table('contracts')
                ->where('estimate_id', $model->id)
                ->exists();
        }

        if ($hasContracts) {
            return response()->json([
                'message' => 'Смета привязана к договору.',
            ], 409);
        }

        DB::connection('legacy_new')->transaction(function () use ($model): void {
            EstimateItemSource::query()->where('estimate_id', $model->id)->delete();
            EstimateItem::query()->where('estimate_id', $model->id)->delete();
            $model->delete();
        });

        return response()->json([
            'status' => 'ok',
        ]);
    }

    private function resolveEstimate(Request $request, int $estimateId): Estimate
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = Estimate::query()->where('id', $estimateId);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->firstOrFail();
    }

    private function resolveCounterpartyId(array $data, ?int $tenantId, ?int $companyId, ?int $userId): ?int
    {
        $clientId = $data['client_id'] ?? null;
        if ($clientId) {
            $existing = $this->findCounterpartyById($clientId, $tenantId, $companyId);
            if ($existing) {
                return $existing->id;
            }
        }

        $phone = trim((string) ($data['client_phone'] ?? ''));
        $counterparty = $this->findCounterpartyByPhone($phone, $tenantId, $companyId);
        if ($counterparty) {
            return $counterparty->id;
        }

        $name = trim((string) ($data['client_name'] ?? ''));
        if ($name === '') {
            return $clientId;
        }

        $counterparty = Counterparty::query()->create([
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            'type' => 'individual',
            'name' => $name,
            'phone' => $phone !== '' ? $phone : null,
            'is_active' => true,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        return $counterparty->id;
    }

    private function findCounterpartyById(int $id, ?int $tenantId, ?int $companyId): ?Counterparty
    {
        $query = Counterparty::query()->where('id', $id);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->first();
    }

    private function findCounterpartyByPhone(string $phone, ?int $tenantId, ?int $companyId): ?Counterparty
    {
        $normalized = Counterparty::normalizePhone($phone);
        if (!$normalized) {
            return null;
        }

        $query = Counterparty::query()->where('phone_normalized', $normalized);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->first();
    }

    public function revokePublic(Request $request, int $estimate): JsonResponse
    {
        $model = $this->resolveEstimate($request, $estimate);
        $model->public_revoked_at = now();
        $model->save();

        return response()->json([
            'data' => (new EstimateResource($model))->toArray($request),
        ]);
    }
}
