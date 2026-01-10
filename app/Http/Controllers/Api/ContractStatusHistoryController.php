<?php

namespace App\Http\Controllers\Api;

use App\Domain\CRM\Models\ContractStatusChange;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContractStatusChangeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractStatusHistoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = $perPage <= 0 ? 10 : min($perPage, 200);
        $page = (int) $request->integer('page', 1);

        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $query = ContractStatusChange::query()
            ->with(['previousStatus', 'newStatus', 'changedBy'])
            ->orderByDesc('changed_at')
            ->orderByDesc('id');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($request->filled('contract_id')) {
            $query->where('contract_id', $request->integer('contract_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('changed_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('changed_at', '<=', $request->date('date_to'));
        }

        $changes = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => ContractStatusChangeResource::collection($changes->items())->toArray($request),
            'meta' => [
                'current_page' => $changes->currentPage(),
                'per_page' => $changes->perPage(),
                'total' => $changes->total(),
                'last_page' => $changes->lastPage(),
            ],
        ]);
    }
}
