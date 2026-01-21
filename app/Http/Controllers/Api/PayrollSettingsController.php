<?php

namespace App\Http\Controllers\Api;

use App\Domain\Finance\Models\PayrollSetting;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollSettingsController extends Controller
{
    private const DEFAULTS = [
        'manager_fixed' => 1000,
        'manager_percent' => 7,
        'measurer_fixed' => 1000,
        'measurer_percent' => 5,
    ];

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $settings = PayrollSetting::query()->firstOrCreate(
            ['tenant_id' => $tenantId, 'company_id' => $companyId],
            self::DEFAULTS
        );

        return response()->json(['data' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'manager_fixed' => ['required', 'numeric', 'min:0'],
            'manager_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'measurer_fixed' => ['required', 'numeric', 'min:0'],
            'measurer_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $settings = PayrollSetting::query()->firstOrCreate(
            ['tenant_id' => $tenantId, 'company_id' => $companyId],
            self::DEFAULTS
        );

        $settings->update($validated);

        return response()->json(['data' => $settings]);
    }
}
