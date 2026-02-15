<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\UpdateFinanceObjectTypeSettingsRequest;
use App\Services\Finance\FinanceObjectTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class FinanceObjectTypeController extends Controller
{
    public function __construct(private readonly FinanceObjectTypeService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        [$tenantId, $companyId] = $this->resolveContext($request);
        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $includeDisabled = $request->boolean('include_disabled', true);
        $rows = $this->service->listForCompany($tenantId, $companyId, $includeDisabled);

        return response()->json([
            'data' => $rows->values(),
        ]);
    }

    public function updateSettings(UpdateFinanceObjectTypeSettingsRequest $request, string $typeKey): JsonResponse
    {
        [$tenantId, $companyId] = $this->resolveContext($request);
        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $payload = $request->validated();
        if ($payload === []) {
            return response()->json(['message' => 'No settings provided for update.'], 422);
        }

        try {
            $row = $this->service->updateSettings($tenantId, $companyId, $typeKey, $payload);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'data' => $row,
        ]);
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function resolveContext(Request $request): array
    {
        $tenantId = $request->user()?->tenant_id ? (int) $request->user()?->tenant_id : null;
        $companyId = $request->user()?->default_company_id
            ? (int) $request->user()?->default_company_id
            : ($request->user()?->company_id ? (int) $request->user()?->company_id : null);

        return [$tenantId, $companyId];
    }
}
