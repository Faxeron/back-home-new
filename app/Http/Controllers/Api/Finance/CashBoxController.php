<?php

namespace App\Http\Controllers\API\Finance;

use App\Domain\Finance\Models\CashBox;
use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashBoxController extends Controller
{
    public function __construct(private readonly FinanceService $financeService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $companyId = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $cashboxes = CashBox::query()
            ->select('cashboxes.*')
            ->distinct()
            ->join('cashbox_company as cc', 'cc.cashbox_id', '=', 'cashboxes.id')
            ->where('cc.company_id', $companyId)
            ->where(function ($builder) use ($tenantId) {
                $builder->whereNull('cashboxes.tenant_id')
                    ->orWhere('cashboxes.tenant_id', $tenantId);
            })
            ->with('logoPreset')
            ->orderBy('cashboxes.name')
            ->get();

        $data = $cashboxes->map(function (CashBox $cashBox) {
            return [
                'id' => $cashBox->id,
                'name' => $cashBox->name,
                'balance' => $this->financeService->getCashBoxBalance($cashBox->id),
                'logo_url' => $this->resolveLogoUrl($cashBox),
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function balance(Request $request, int $cashBoxId): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $companyId = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $cashBox = CashBox::query()
            ->select('cashboxes.*')
            ->join('cashbox_company as cc', 'cc.cashbox_id', '=', 'cashboxes.id')
            ->where('cashboxes.id', $cashBoxId)
            ->where('cc.company_id', $companyId)
            ->where(function ($builder) use ($tenantId) {
                $builder->whereNull('cashboxes.tenant_id')
                    ->orWhere('cashboxes.tenant_id', $tenantId);
            })
            ->with('logoPreset')
            ->first();

        if (!$cashBox) {
            return response()->json(['message' => 'Cash box not found.'], 404);
        }

        $balance = $this->financeService->getCashBoxBalance($cashBox->id);

        return response()->json([
            'balance' => $balance,
            'logo_url' => $this->resolveLogoUrl($cashBox),
        ]);
    }

    private function resolveLogoUrl(CashBox $cashBox): ?string
    {
        if ($cashBox->logo_source === 'preset' && $cashBox->logoPreset?->file_path) {
            return $this->publicStorageUrl($cashBox->logoPreset->file_path);
        }

        if ($cashBox->logo_path) {
            return $this->publicStorageUrl($cashBox->logo_path);
        }

        return null;
    }

    private function publicStorageUrl(string $path): string
    {
        // Return a stable, host-agnostic URL. Frontend resolves it using API origin.
        $normalized = ltrim($path, '/');
        return '/storage/' . $normalized;
    }
}
