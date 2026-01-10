<?php

namespace App\Http\Controllers\API\Finance;

use App\Domain\Finance\Models\PaymentMethod;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $companyId = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $data = PaymentMethod::query()
            ->where(function ($builder) use ($tenantId) {
                $builder->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenantId);
            })
            ->where(function ($builder) use ($companyId) {
                $builder->where('company_id', $companyId)
                    ->orWhere('is_global', true);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'code', 'name', 'is_active', 'sort_order']);

        return response()->json(['data' => $data]);
    }
}
