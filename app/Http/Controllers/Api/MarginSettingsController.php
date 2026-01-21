<?php

namespace App\Http\Controllers\Api;

use App\Domain\Finance\Models\MarginSetting;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarginSettingsController extends Controller
{
    private const DEFAULTS = [
        'red_max' => 10,
        'orange_max' => 20,
    ];

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $settings = MarginSetting::query()->firstOrCreate(
            ['tenant_id' => $tenantId, 'company_id' => $companyId],
            self::DEFAULTS
        );

        return response()->json(['data' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'red_max' => ['required', 'numeric', 'min:0', 'max:100'],
            'orange_max' => ['required', 'numeric', 'min:0', 'max:100', 'gte:red_max'],
        ]);

        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $settings = MarginSetting::query()->firstOrCreate(
            ['tenant_id' => $tenantId, 'company_id' => $companyId],
            self::DEFAULTS
        );

        $settings->update($validated);

        return response()->json(['data' => $settings]);
    }
}
