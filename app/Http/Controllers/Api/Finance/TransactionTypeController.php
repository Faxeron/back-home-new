<?php

namespace App\Http\Controllers\API\Finance;

use App\Domain\Finance\Models\TransactionType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $companyId = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $query = TransactionType::query();
        $query->where(function ($builder) use ($tenantId) {
            $builder->whereNull('tenant_id')
                ->orWhere('tenant_id', $tenantId);
        });
        $query->where(function ($builder) use ($companyId) {
            $builder->where('company_id', $companyId)
                ->orWhere('is_global', true);
        });

        $search = trim((string) $request->get('q', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $query->orderBy('sort_order')->orderBy('id');

        if ($request->has('per_page') || $request->has('page') || $search !== '') {
            $perPage = (int) $request->integer('per_page', 25);
            $perPage = $perPage <= 0 ? 25 : min($perPage, 200);
            $page = (int) $request->integer('page', 1);

            $items = $query->paginate($perPage, ['id', 'code', 'name', 'sign', 'is_active', 'sort_order'], 'page', $page);

            return response()->json([
                'data' => collect($items->items()),
                'meta' => [
                    'current_page' => $items->currentPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                    'last_page' => $items->lastPage(),
                ],
            ]);
        }

        $data = $query->get(['id', 'code', 'name', 'sign', 'is_active', 'sort_order']);

        return response()->json(['data' => $data]);
    }
}
