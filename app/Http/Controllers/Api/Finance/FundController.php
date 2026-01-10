<?php

namespace App\Http\Controllers\API\Finance;

use App\Domain\Finance\Models\SpendingFund;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FundController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $companyId = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $data = SpendingFund::query()
            ->where(function ($builder) use ($tenantId) {
                $builder->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenantId);
            })
            ->where(function ($builder) use ($companyId) {
                $builder->where('company_id', $companyId)
                    ->orWhere('is_global', true);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'is_active']);

        return response()->json(['data' => $data]);
    }
}
