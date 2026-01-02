<?php

namespace App\Http\Controllers\Api\Estimates;

use App\Domain\Estimates\Models\Estimate;
use App\Domain\CRM\Models\Counterparty;
use App\Http\Controllers\Controller;
use App\Http\Requests\Estimates\EstimateStoreRequest;
use App\Http\Requests\Estimates\EstimateUpdateRequest;
use App\Http\Resources\EstimateResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EstimateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = $perPage <= 0 ? 10 : min($perPage, 200);
        $page = (int) $request->integer('page', 1);

        $tenantId = $request->user()?->tenant_id ?? $request->integer('tenant_id') ?: null;
        $companyId = $request->user()?->company_id ?? $request->integer('company_id') ?: null;

        $query = Estimate::query()
            ->with('counterparty')
            ->withCount('items')
            ->withSum('items as total_sum', 'total')
            ->orderByDesc('id');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId) {
            $query->where(function ($builder) use ($companyId) {
                $builder->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            });
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
        $tenantId = $request->user()?->tenant_id ?? $request->integer('tenant_id') ?: null;
        $companyId = $request->user()?->company_id ?? $request->integer('company_id') ?: null;

        $randomId = Str::random(12);
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
            'link_montaj' => "/estimate/{$randomId}/montaj",
            'sms_sent' => 0,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
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

    private function resolveEstimate(Request $request, int $estimateId): Estimate
    {
        $tenantId = $request->user()?->tenant_id ?? $request->integer('tenant_id') ?: null;
        $companyId = $request->user()?->company_id ?? $request->integer('company_id') ?: null;

        $query = Estimate::query()->where('id', $estimateId);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId) {
            $query->where(function ($builder) use ($companyId) {
                $builder->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            });
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

        if ($companyId) {
            $query->where(function ($builder) use ($companyId) {
                $builder->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            });
        }

        return $query->first();
    }

    private function findCounterpartyByPhone(string $phone, ?int $tenantId, ?int $companyId): ?Counterparty
    {
        $normalized = $this->normalizePhone($phone);
        if ($normalized === '') {
            return null;
        }

        $search = strlen($normalized) > 10 ? substr($normalized, -10) : $normalized;

        $query = Counterparty::query()->whereNotNull('phone');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId) {
            $query->where(function ($builder) use ($companyId) {
                $builder->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            });
        }

        $candidates = $query->where('phone', 'like', "%{$search}%")
            ->limit(25)
            ->get();

        foreach ($candidates as $candidate) {
            if ($this->normalizePhone((string) $candidate->phone) === $normalized) {
                return $candidate;
            }
        }

        return null;
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}
