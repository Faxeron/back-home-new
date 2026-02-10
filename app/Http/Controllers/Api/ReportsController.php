<?php

namespace App\Http\Controllers\Api;

use App\Services\Finance\ReportBuilderService;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    /**
     * GET /api/reports/cashflow/daily
     * Daily cashflow data for charts and tables.
     * Query params: company_id (optional), from, to, section, direction, cashflow_item_id.
     */
    public function cashflowDaily(Request $request): JsonResponse
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

        $from = $request->get('from', date('Y-m-d', strtotime('-30 days')));
        $to = $request->get('to', date('Y-m-d'));
        $section = $request->get('section');
        $direction = $request->get('direction');

        $query = DB::connection('legacy_new')
            ->table('report_cashflow_daily')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->whereBetween('day_date', [$from, $to]);

        if ($section) {
            $query->where('section', $section);
        }

        if ($direction) {
            $query->where('direction', $direction);
        }

        $data = $query
            ->orderBy('day_date', 'asc')
            ->select('day_date', 'section', 'direction', 'cashflow_item_name', 'total_amount')
            ->get();

        // ApexCharts format: array of points
        $formatted = $data->map(static function ($row) {
            return [
                'x' => $row->day_date,
                'y' => (float) $row->total_amount,
                'label' => "{$row->section} - {$row->direction}: {$row->cashflow_item_name}",
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formatted,
            'count' => $data->count(),
            'period' => "{$from} to {$to}",
        ]);
    }

    /**
     * GET /api/reports/cashflow/monthly-summary
     * CEO dashboard: monthly cashflow summary.
     * Query params: company_id (optional), from_month, to_month.
     */
    public function cashflowMonthlySummary(Request $request): JsonResponse
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

        $fromMonth = $request->get('from_month', date('Y-m', strtotime('-12 months')));
        $toMonth = $request->get('to_month', date('Y-m'));

        $data = DB::connection('legacy_new')
            ->table('report_cashflow_monthly_summary')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->whereBetween('year_month', [$fromMonth, $toMonth])
            ->orderBy('year_month', 'asc')
            ->select(
                'year_month',
                'opening_balance',
                'inflow_total',
                'outflow_total',
                'net_cashflow',
                'closing_balance'
            )
            ->get();

        // Format for ApexCharts: transform to numeric values
        $formatted = $data->map(static function ($row) {
            return [
                'month' => $row->year_month,
                'opening_balance' => (float) $row->opening_balance,
                'inflow_total' => (float) $row->inflow_total,
                'outflow_total' => (float) $row->outflow_total,
                'net_cashflow' => (float) $row->net_cashflow,
                'closing_balance' => (float) $row->closing_balance,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formatted,
            'count' => $data->count(),
            'period' => "{$fromMonth} to {$toMonth}",
        ]);
    }

    /**
     * GET /api/reports/pnl/monthly
     * Profit & Loss monthly data.
     * Query params: company_id (optional), from_month, to_month.
     */
    public function pnlMonthly(Request $request): JsonResponse
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

        $fromMonth = $request->get('from_month', date('Y-m', strtotime('-12 months')));
        $toMonth = $request->get('to_month', date('Y-m'));

        $data = DB::connection('legacy_new')
            ->table('report_pnl_monthly')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->whereBetween('year_month', [$fromMonth, $toMonth])
            ->orderBy('year_month', 'asc')
            ->select(
                'year_month',
                'revenue_operating',
                'expense_operating',
                'operating_profit',
                'finance_in',
                'finance_out'
            )
            ->get();

        // Format with totals
        $formatted = $data->map(static function ($row) {
            $revenue = (float) $row->revenue_operating;
            $expenses = (float) $row->expense_operating;
            $financeIn = (float) $row->finance_in;
            $financeOut = (float) $row->finance_out;

            return [
                'month' => $row->year_month,
                'revenue_operating' => $revenue,
                'expense_operating' => $expenses,
                'operating_profit' => (float) $row->operating_profit,
                'finance_in' => $financeIn,
                'finance_out' => $financeOut,
                'net_result' => (float) $row->operating_profit + $financeIn - $financeOut,
            ];
        });

        // Calculate totals
        $totals = [
            'revenue_operating' => (float) $data->sum('revenue_operating'),
            'expense_operating' => (float) $data->sum('expense_operating'),
            'operating_profit' => (float) $data->sum('operating_profit'),
            'finance_in' => (float) $data->sum('finance_in'),
            'finance_out' => (float) $data->sum('finance_out'),
        ];

        return response()->json([
            'success' => true,
            'data' => $formatted,
            'totals' => $totals,
            'count' => $data->count(),
            'period' => "{$fromMonth} to {$toMonth}",
        ]);
    }

    /**
     * GET /api/reports/debts/daily
     * AR/AP debt snapshots.
     * Query params: company_id (optional), date, type (AR/AP).
     */
    public function debtsDaily(Request $request): JsonResponse
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

        $date = $request->get('date', date('Y-m-d'));
        $type = $request->get('type', 'AR'); // AR or AP

        $query = DB::connection('legacy_new')
            ->table('report_debts_daily')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('snapshot_date', $date);

        if ($type && in_array($type, ['AR', 'AP'], true)) {
            $query->where('type', $type);
        }

        $data = $query
            ->orderBy('days_overdue', 'desc')
            ->select(
                'type',
                'entity_id',
                'entity_title',
                'amount_total',
                'amount_paid',
                'amount_debt',
                'days_overdue'
            )
            ->get();

        // Format for table display
        $formatted = $data->map(static function ($row) {
            return [
                'type' => $row->type,
                'entity_id' => $row->entity_id,
                'entity_title' => $row->entity_title,
                'amount_total' => (float) $row->amount_total,
                'amount_paid' => (float) $row->amount_paid,
                'amount_debt' => (float) $row->amount_debt,
                'days_overdue' => (int) $row->days_overdue,
                'status' => $row->days_overdue > 30 ? 'overdue' : 'current',
            ];
        });

        // Summary stats
        $summary = [
            'total_debt' => (float) $data->sum('amount_debt'),
            'total_paid' => (float) $data->sum('amount_paid'),
            'total_amount' => (float) $data->sum('amount_total'),
            'records_count' => $data->count(),
            'overdue_count' => $data->where('days_overdue', '>', 30)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $formatted,
            'summary' => $summary,
            'date' => $date,
            'type' => $type,
        ]);
    }

    /**
     * GET /api/reports/debts/summary
     * Summary of AR/AP by type.
     * Query params: company_id (optional), date.
     */
    public function debtsSummary(Request $request): JsonResponse
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

        $date = $request->get('date', date('Y-m-d'));

        $data = DB::connection('legacy_new')
            ->table('report_debts_daily')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('snapshot_date', $date)
            ->selectRaw('type, COUNT(*) as records, SUM(amount_debt) as total_debt, SUM(amount_total) as total_amount')
            ->groupBy('type')
            ->get();

        $formatted = $data->map(static function ($row) {
            return [
                'type' => $row->type,
                'type_name' => $row->type === 'AR' ? 'Дебиторская задолженность' : 'Кредиторская задолженность',
                'records' => (int) $row->records,
                'total_debt' => (float) $row->total_debt,
                'total_amount' => (float) $row->total_amount,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formatted,
            'date' => $date,
        ]);
    }

    /**
     * POST /api/reports/rebuild
     * Rebuild materialized report tables for CEO reports.
     *
     * Query/body params:
     * - company_id (optional; falls back to user's default)
     * - from_month (optional, YYYY-MM; defaults to -12 months)
     * - to_month (optional, YYYY-MM; defaults to current month)
     * - force (optional bool; rebuild even if finance_periods status=CLOSED)
     */
    public function rebuild(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $defaultCompanyId = $user?->default_company_id ?? $user?->company_id;

        if (!$tenantId) {
            return response()->json(['message' => 'Missing tenant context.'], 403);
        }

        $companyId = (int) $request->integer('company_id') ?: (int) ($defaultCompanyId ?? 0);
        if ($companyId <= 0) {
            return response()->json(['message' => 'Missing company_id.'], 422);
        }

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Ensure user belongs to the requested company (tenant isolation).
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

        $fromMonth = $request->string('from_month')->toString() ?: date('Y-m', strtotime('-12 months'));
        $toMonth = $request->string('to_month')->toString() ?: date('Y-m');
        $force = $request->boolean('force', false);

        try {
            $fromMonthDate = Carbon::createFromFormat('Y-m', $fromMonth)->startOfMonth();
            $toMonthDate = Carbon::createFromFormat('Y-m', $toMonth)->startOfMonth();
        } catch (\Throwable) {
            return response()->json(['message' => 'Invalid from_month/to_month format. Expected YYYY-MM.'], 422);
        }

        if ($fromMonthDate->gt($toMonthDate)) {
            return response()->json(['message' => 'from_month must be <= to_month.'], 422);
        }

        $fromDate = $fromMonthDate->copy()->startOfMonth()->toDateString();
        $toDateCandidate = $toMonthDate->copy()->endOfMonth();
        $toDate = Carbon::today()->lt($toDateCandidate) ? Carbon::today()->toDateString() : $toDateCandidate->toDateString();

        $txBase = DB::connection('legacy_new')
            ->table('transactions')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('is_paid', 1)
            ->whereNotNull('date_is_paid')
            ->whereBetween('date_is_paid', [$fromDate, $toDate]);

        $paidTotal = (clone $txBase)->count();
        $paidWithCashflow = (clone $txBase)->whereNotNull('cashflow_item_id')->count();
        $paidWithoutCashflow = max(0, $paidTotal - $paidWithCashflow);

        // Clear report tables in the requested period to avoid stale rows when data is edited.
        DB::connection('legacy_new')
            ->table('report_cashflow_daily')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->whereBetween('day_date', [$fromDate, $toDate])
            ->delete();

        // Build only for days that actually have paid transactions with a cashflow item.
        $dates = (clone $txBase)
            ->whereNotNull('cashflow_item_id')
            ->where('cashflow_item_id', '>', 0)
            ->distinct()
            ->orderBy('date_is_paid', 'asc')
            ->pluck('date_is_paid')
            ->all();

        $service = new ReportBuilderService();
        $service->setContext((int) $tenantId, $companyId);

        $daysSuccess = 0;
        $daysSkipped = 0;
        foreach ($dates as $date) {
            $result = $service->rebuildCashflowDay((string) $date, $force);
            if (($result['success'] ?? false) === true) {
                $daysSuccess++;
            } elseif (($result['skipped'] ?? false) === true) {
                $daysSkipped++;
            }
        }

        $monthsSuccess = 0;
        $monthsSkipped = 0;
        $pnlSuccess = 0;
        $pnlSkipped = 0;

        $cursor = $fromMonthDate->copy();
        while ($cursor->lte($toMonthDate)) {
            $ym = $cursor->format('Y-m');

            $mRes = $service->rebuildCashflowMonth($ym, $force);
            if (($mRes['success'] ?? false) === true) {
                $monthsSuccess++;
            } elseif (($mRes['skipped'] ?? false) === true) {
                $monthsSkipped++;
            }

            $pRes = $service->rebuildPnLMonth($ym, $force);
            if (($pRes['success'] ?? false) === true) {
                $pnlSuccess++;
            } elseif (($pRes['skipped'] ?? false) === true) {
                $pnlSkipped++;
            }

            $cursor->addMonth();
        }

        $debtResult = $service->snapshotDebts(Carbon::today()->toDateString());

        return response()->json([
            'success' => true,
            'tenant_id' => (int) $tenantId,
            'company_id' => $companyId,
            'from_month' => $fromMonth,
            'to_month' => $toMonth,
            'source_stats' => [
                'paid_total' => $paidTotal,
                'paid_with_cashflow_item' => $paidWithCashflow,
                'paid_without_cashflow_item' => $paidWithoutCashflow,
            ],
            'built' => [
                'days_success' => $daysSuccess,
                'days_skipped' => $daysSkipped,
                'months_success' => $monthsSuccess,
                'months_skipped' => $monthsSkipped,
                'pnl_months_success' => $pnlSuccess,
                'pnl_months_skipped' => $pnlSkipped,
                'debts' => $debtResult,
            ],
        ]);
    }
}
