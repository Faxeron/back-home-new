<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnsureCompanyContext
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $tenantId = $user->tenant_id;
        $companyId = $user->default_company_id ?? $user->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $member = DB::connection('legacy_new')
            ->table('user_company')
            ->where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->exists();

        if (!$member) {
            return response()->json(['message' => 'User is not assigned to the company.'], 403);
        }

        $companyTenantId = DB::connection('legacy_new')
            ->table('companies')
            ->where('id', $companyId)
            ->value('tenant_id');

        if ($companyTenantId && (int) $companyTenantId !== (int) $tenantId) {
            return response()->json(['message' => 'Company does not belong to tenant.'], 403);
        }

        $request->attributes->set('tenant_id', $tenantId);
        $request->attributes->set('company_id', $companyId);

        return $next($request);
    }
}
