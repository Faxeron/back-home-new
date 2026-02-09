<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Finance\CashflowReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashflowReportController extends Controller
{
    public function __construct(private readonly CashflowReportService $service)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $defaultCompanyId = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$tenantId) {
            return response()->json(['message' => 'Missing tenant context.'], 403);
        }

        $companyId = (int) $request->integer('company_id') ?: (int) ($defaultCompanyId ?? 0);
        if ($companyId <= 0) {
            return response()->json(['message' => 'Missing company_id.'], 422);
        }

        $from = $request->date('date_from');
        $to = $request->date('date_to');

        $from = $from ? $from->copy()->startOfDay() : now()->startOfMonth();
        $to = $to ? $to->copy()->endOfDay() : now()->endOfMonth();

        $cashboxId = $request->integer('cashbox_id') ?: null;
        $groupBy = $request->string('group_by')->toString() ?: null;
        $groupBy = $groupBy ? strtolower($groupBy) : null;
        if ($groupBy && !in_array($groupBy, ['day', 'week', 'month'], true)) {
            return response()->json(['message' => 'Invalid group_by.'], 422);
        }

        $data = $this->service->buildReport(
            (int) $tenantId,
            $companyId,
            $from->toDateString(),
            $to->toDateString(),
            $cashboxId,
            $groupBy,
        );

        return response()->json(['data' => $data]);
    }
}
