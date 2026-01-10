<?php

namespace App\Http\Controllers\API\Finance;

use App\Domain\CRM\Models\Counterparty;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CounterpartyLookupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $companyId = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $query = Counterparty::query()->orderBy('name');

        $query->where('tenant_id', $tenantId);
        $query->where('company_id', $companyId);

        $data = $query->get(['id', 'type', 'name', 'phone', 'email', 'is_active']);

        return response()->json(['data' => $data]);
    }
}
