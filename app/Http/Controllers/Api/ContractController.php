<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContractResource;
use App\Domain\CRM\Models\Contract;
use App\Domain\CRM\Models\ContractStatusChange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

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
            ->with(['counterparty', 'status', 'saleType', 'manager', 'measurer'])
            ->withSum('receipts as receipts_total', 'sum')
            ->orderByDesc('contract_date');

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
            ->with(['counterparty', 'status', 'saleType', 'manager', 'measurer'])
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
}
