<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContractResource;
use App\Domain\CRM\Models\Contract;
use App\Domain\CRM\Models\ContractDocument;
use App\Domain\CRM\Models\ContractGroup;
use App\Domain\CRM\Models\ContractItem;
use App\Domain\CRM\Models\ContractStatusChange;
use App\Domain\Finance\Models\FinanceAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ContractController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = $perPage <= 0 ? 10 : min($perPage, 200);
        $page = (int) $request->integer('page', 1);
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = Contract::query()
            ->with(['counterparty.individual', 'counterparty.company', 'status', 'saleType', 'manager', 'measurer'])
            ->withSum('receipts as receipts_total', 'sum')
            ->orderByDesc('id');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhereHas('counterparty', function ($counterpartyQuery) use ($search) {
                        $counterpartyQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status_id'))
            $query->where('contract_status_id', $request->integer('status_id'));

        if ($request->filled('counterparty_id'))
            $query->where('counterparty_id', $request->integer('counterparty_id'));

        $contracts = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => collect($contracts->items())->map(
                fn (Contract $contract) => (new ContractResource($contract))->toArray($request),
            ),
            'meta' => [
                'current_page' => $contracts->currentPage(),
                'per_page' => $contracts->perPage(),
                'total' => $contracts->total(),
                'last_page' => $contracts->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $contract): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = Contract::query()
            ->with(['counterparty.individual', 'counterparty.company', 'status', 'saleType', 'manager', 'measurer'])
            ->withSum('receipts as receipts_total', 'sum')
            ->where('id', $contract);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $model = $query->firstOrFail();

        return response()->json([
            'data' => (new ContractResource($model))->toArray($request),
        ]);
    }

    public function updateStatus(Request $request, int $contract): JsonResponse
    {
        $validated = $request->validate([
            'contract_status_id' => ['required', 'integer', 'exists:legacy_new.contract_statuses,id'],
        ]);

        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = Contract::query()->where('id', $contract);
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $model = $query->firstOrFail();

        Gate::authorize('update', $model);

        $nextStatusId = (int) $validated['contract_status_id'];
        $previousStatusId = $model->contract_status_id ? (int) $model->contract_status_id : null;

        if ($previousStatusId !== $nextStatusId) {
            DB::connection('legacy_new')->transaction(function () use ($model, $nextStatusId, $previousStatusId, $tenantId, $companyId, $user) {
                $model->update([
                    'contract_status_id' => $nextStatusId,
                ]);

                ContractStatusChange::create([
                    'tenant_id' => $tenantId,
                    'company_id' => $companyId,
                    'contract_id' => $model->id,
                    'previous_status_id' => $previousStatusId,
                    'new_status_id' => $nextStatusId,
                    'changed_by' => $user?->id,
                    'changed_at' => now(),
                ]);
            });
        }

        $model->load(['counterparty', 'status', 'saleType', 'manager', 'measurer']);
        $model->loadSum('receipts as receipts_total', 'sum');

        return response()->json((new ContractResource($model))->toArray($request));
    }

    public function update(Request $request, int $contract): JsonResponse
    {
        $validated = $request->validate([
            'contract_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'total_amount' => ['nullable', 'numeric'],
            'city_id' => ['nullable', 'integer'],
            'sale_type_id' => ['nullable', 'integer'],
            'work_start_date' => ['nullable', 'date'],
            'work_end_date' => ['nullable', 'date'],
        ]);

        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = Contract::query()->where('id', $contract);
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $model = $query->firstOrFail();

        $model->fill([
            'contract_date' => $validated['contract_date'] ?? $model->contract_date,
            'address' => $validated['address'] ?? $model->address,
            'total_amount' => $validated['total_amount'] ?? $model->total_amount,
            'city_id' => $validated['city_id'] ?? $model->city_id,
            'sale_type_id' => $validated['sale_type_id'] ?? $model->sale_type_id,
            'work_start_date' => $validated['work_start_date'] ?? $model->work_start_date,
            'work_end_date' => $validated['work_end_date'] ?? $model->work_end_date,
            'updated_by' => $user?->id,
        ]);
        $model->save();

        $changes = $model->getChanges();
        if (!empty($changes)) {
            FinanceAuditLog::create([
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'user_id' => $user?->id,
                'action' => 'contract.updated',
                'payload' => [
                    'contract_id' => $model->id,
                    'changes' => array_keys($changes),
                ],
                'created_at' => now(),
            ]);
        }

        $model->load(['counterparty.individual', 'counterparty.company', 'status', 'saleType', 'manager', 'measurer']);
        $model->loadSum('receipts as receipts_total', 'sum');

        return response()->json((new ContractResource($model))->toArray($request));
    }

    public function destroy(Request $request, int $contract): JsonResponse
    {
        $this->ensureAdmin($request);

        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = Contract::query()->where('id', $contract);
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $model = $query->firstOrFail();
        $groupId = $model->contract_group_id;

        DB::connection('legacy_new')->transaction(function () use ($model, $groupId, $tenantId, $companyId): void {
            $documents = ContractDocument::query()
                ->where('contract_id', $model->id)
                ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->get();

            foreach ($documents as $document) {
                if ($document->file_path) {
                    Storage::disk('local')->delete($document->file_path);
                }
            }

            ContractDocument::query()
                ->where('contract_id', $model->id)
                ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->delete();

            ContractItem::query()
                ->where('contract_id', $model->id)
                ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->delete();

            ContractStatusChange::query()
                ->where('contract_id', $model->id)
                ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->delete();

            $model->delete();

            if ($groupId) {
                $hasContracts = Contract::query()
                    ->where('contract_group_id', $groupId)
                    ->exists();

                if (!$hasContracts) {
                    ContractGroup::query()->where('id', $groupId)->delete();
                }
            }
        });

        return response()->json(['status' => 'ok']);
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'Only admins can delete.');
        }

        $userId = (int) $user->id;
        $db = DB::connection('legacy_new');
        $isAdmin = false;

        if (Schema::connection('legacy_new')->hasTable('role_users') && Schema::connection('legacy_new')->hasTable('roles')) {
            $isAdmin = $db->table('role_users')
                ->join('roles', 'roles.id', '=', 'role_users.role_id')
                ->where('role_users.user_id', $userId)
                ->where(function ($query) {
                    $query->where('roles.code', 'admin')
                        ->orWhere('roles.name', 'Админ')
                        ->orWhere('roles.name', 'Admin');
                })
                ->exists();
        }

        $isOwner = false;
        if (Schema::connection('legacy_new')->hasTable('user_company')) {
            $isOwner = $db->table('user_company')
                ->where('user_id', $userId)
                ->where('role', 'owner')
                ->exists();
        }

        if (!$isAdmin && !$isOwner && $userId !== 1) {
            abort(403, 'Only admins can delete.');
        }
    }
}
