<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Domain\Finance\Models\CashflowItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportBuilderService
{
    protected int $tenantId;
    protected int $companyId;

    public function setContext(int $tenantId, int $companyId): self
    {
        $this->tenantId = $tenantId;
        $this->companyId = $companyId;

        return $this;
    }

    /**
     * Rebuild daily cashflow report for a specific date
     */
    public function rebuildCashflowDay(string $dateYmd, bool $force = false): array
    {
        $period = $this->getPeriodStatus(date('Y-m', strtotime($dateYmd)));
        if ($period?->status === 'CLOSED' && !$force) {
            return ['skipped' => true, 'reason' => 'Period is CLOSED'];
        }

        $date = Carbon::parse($dateYmd);
        $yearMonth = $date->format('Y-m');

        return DB::connection('legacy_new')->transaction(function () use ($date, $yearMonth, $dateYmd) {
            // Clear old snapshot for the day to avoid stale rows when transactions are edited.
            DB::connection('legacy_new')
                ->table('report_cashflow_daily')
                ->where('tenant_id', $this->tenantId)
                ->where('company_id', $this->companyId)
                ->where('day_date', $dateYmd)
                ->delete();

            // Get aggregated transactions for the day, excluding transfers
            $data = DB::connection('legacy_new')
                ->table('transactions as t')
                ->selectRaw('
                    t.cashflow_item_id,
                    c.section,
                    c.direction,
                    c.code,
                    c.name,
                    SUM(t.sum) as total_amount,
                    COUNT(t.id) as tx_count
                ')
                ->leftJoin('cashflow_items as c', 't.cashflow_item_id', '=', 'c.id')
                ->where('t.is_paid', 1)
                ->where('t.date_is_paid', $dateYmd)
                ->whereNotNull('t.cashflow_item_id')
                ->where('t.cashflow_item_id', '>', 0)
                ->where('t.tenant_id', $this->tenantId)
                ->where('t.company_id', $this->companyId)
                ->whereNotIn('t.id', function ($q) {
                    // Exclude transactions that are part of cash_transfers (as transaction_in_id)
                    // IMPORTANT: exclude NULLs, otherwise SQL NOT IN (NULL, ...) filters out everything.
                    $q->select('transaction_in_id')->from('cash_transfers')
                        ->where('tenant_id', $this->tenantId)
                        ->where('company_id', $this->companyId)
                        ->whereNotNull('transaction_in_id');
                })
                ->whereNotIn('t.id', function ($q) {
                    // Exclude transactions that are part of cash_transfers (as transaction_out_id)
                    // IMPORTANT: exclude NULLs, otherwise SQL NOT IN (NULL, ...) filters out everything.
                    $q->select('transaction_out_id')->from('cash_transfers')
                        ->where('tenant_id', $this->tenantId)
                        ->where('company_id', $this->companyId)
                        ->whereNotNull('transaction_out_id');
                })
                ->groupBy('t.cashflow_item_id', 'c.section', 'c.direction', 'c.code', 'c.name')
                ->get();

            $count = 0;
            foreach ($data as $row) {
                DB::connection('legacy_new')->table('report_cashflow_daily')->upsert([
                    'tenant_id' => $this->tenantId,
                    'company_id' => $this->companyId,
                    'day_date' => $dateYmd,
                    'year_month' => $yearMonth,
                    'section' => $row->section,
                    'direction' => $row->direction,
                    'cashflow_item_id' => $row->cashflow_item_id,
                    'cashflow_item_name' => $row->name,
                    'total_amount' => $row->total_amount,
                    'tx_count' => $row->tx_count,
                    'updated_at' => now(),
                ], ['tenant_id', 'company_id', 'day_date', 'cashflow_item_id']);
                $count++;
            }

            return ['success' => true, 'date' => $dateYmd, 'records' => $count];
        });
    }

    /**
     * Rebuild monthly cashflow report
     */
    public function rebuildCashflowMonth(string $yearMonth, bool $force = false): array
    {
        $period = $this->getPeriodStatus($yearMonth);
        if ($period?->status === 'CLOSED' && !$force) {
            return ['skipped' => true, 'reason' => 'Period is CLOSED'];
        }

        return DB::connection('legacy_new')->transaction(function () use ($yearMonth) {
            // Clear old month rows to avoid stale rows when day-level data changes.
            DB::connection('legacy_new')
                ->table('report_cashflow_monthly')
                ->where('tenant_id', $this->tenantId)
                ->where('company_id', $this->companyId)
                ->where('year_month', $yearMonth)
                ->delete();

            // Aggregate from daily to monthly
            $data = DB::connection('legacy_new')
                ->table('report_cashflow_daily')
                ->selectRaw('
                    section,
                    direction,
                    cashflow_item_id,
                    cashflow_item_name,
                    SUM(total_amount) as total_amount,
                    SUM(tx_count) as tx_count
                ')
                ->where('tenant_id', $this->tenantId)
                ->where('company_id', $this->companyId)
                ->where('year_month', $yearMonth)
                ->groupBy('section', 'direction', 'cashflow_item_id', 'cashflow_item_name')
                ->get();

            [$year, $month] = explode('-', $yearMonth);

            $count = 0;
            foreach ($data as $row) {
                DB::connection('legacy_new')->table('report_cashflow_monthly')->upsert([
                    'tenant_id' => $this->tenantId,
                    'company_id' => $this->companyId,
                    'year' => (int)$year,
                    'month' => (int)$month,
                    'year_month' => $yearMonth,
                    'section' => $row->section,
                    'direction' => $row->direction,
                    'cashflow_item_id' => $row->cashflow_item_id,
                    'cashflow_item_name' => $row->cashflow_item_name,
                    'total_amount' => $row->total_amount,
                    'tx_count' => $row->tx_count,
                    'updated_at' => now(),
                ], ['tenant_id', 'company_id', 'year_month', 'cashflow_item_id']);
                $count++;
            }

            // Rebuild summary
            $this->rebuildCashflowMonthlySummary($yearMonth);

            return ['success' => true, 'month' => $yearMonth, 'records' => $count];
        });
    }

    /**
     * Rebuild monthly cashflow summary (CEO's quick view)
     */
    public function rebuildCashflowMonthlySummary(string $yearMonth): void
    {
        // Aggregate in/out flows
        $summary = DB::connection('legacy_new')
            ->table('report_cashflow_monthly')
            ->selectRaw('
                SUM(CASE WHEN direction = "IN" THEN total_amount ELSE 0 END) as inflow_total,
                SUM(CASE WHEN direction = "OUT" THEN total_amount ELSE 0 END) as outflow_total
            ')
            ->where('tenant_id', $this->tenantId)
            ->where('company_id', $this->companyId)
            ->where('year_month', $yearMonth)
            ->first();

        $inflow = $summary->inflow_total ?? 0;
        $outflow = $summary->outflow_total ?? 0;
        $netCashflow = $inflow - $outflow;

        // Calculate opening balance (sum of all transactions before this month)
        $yearMonthStart = Carbon::parse($yearMonth . '-01')->startOfMonth();
        $openingBalance = DB::connection('legacy_new')
            ->table('report_cashflow_daily')
            ->selectRaw('SUM(CASE WHEN direction = "IN" THEN total_amount ELSE 0 END) - SUM(CASE WHEN direction = "OUT" THEN total_amount ELSE 0 END) as balance')
            ->where('tenant_id', $this->tenantId)
            ->where('company_id', $this->companyId)
            ->where('day_date', '<', $yearMonthStart->toDateString())
            ->first()?->balance ?? 0;

        $closingBalance = $openingBalance + $netCashflow;

        DB::connection('legacy_new')->table('report_cashflow_monthly_summary')->upsert([
            'tenant_id' => $this->tenantId,
            'company_id' => $this->companyId,
            'year_month' => $yearMonth,
            'opening_balance' => $openingBalance,
            'inflow_total' => $inflow,
            'outflow_total' => $outflow,
            'net_cashflow' => $netCashflow,
            'closing_balance' => $closingBalance,
            'updated_at' => now(),
        ], ['tenant_id', 'company_id', 'year_month']);
    }

    /**
     * Rebuild P&L monthly report
     */
    public function rebuildPnLMonth(string $yearMonth, bool $force = false): array
    {
        $period = $this->getPeriodStatus($yearMonth);
        if ($period?->status === 'CLOSED' && !$force) {
            return ['skipped' => true, 'reason' => 'Period is CLOSED'];
        }

        return DB::connection('legacy_new')->transaction(function () use ($yearMonth) {
            // Clear old by-item rows to avoid stale items when monthly cashflow changes.
            DB::connection('legacy_new')
                ->table('report_pnl_monthly_by_item')
                ->where('tenant_id', $this->tenantId)
                ->where('company_id', $this->companyId)
                ->where('year_month', $yearMonth)
                ->delete();

            // Revenue: OPERATING + IN
            $revenue = DB::connection('legacy_new')
                ->table('report_cashflow_monthly')
                ->where('tenant_id', $this->tenantId)
                ->where('company_id', $this->companyId)
                ->where('year_month', $yearMonth)
                ->where('section', 'OPERATING')
                ->where('direction', 'IN')
                ->sum('total_amount');

            // Expenses: OPERATING + OUT
            $expenses = DB::connection('legacy_new')
                ->table('report_cashflow_monthly')
                ->where('tenant_id', $this->tenantId)
                ->where('company_id', $this->companyId)
                ->where('year_month', $yearMonth)
                ->where('section', 'OPERATING')
                ->where('direction', 'OUT')
                ->sum('total_amount');

            // Finance IN/OUT
            $financeIn = DB::connection('legacy_new')
                ->table('report_cashflow_monthly')
                ->where('tenant_id', $this->tenantId)
                ->where('company_id', $this->companyId)
                ->where('year_month', $yearMonth)
                ->where('section', 'FINANCING')
                ->where('direction', 'IN')
                ->sum('total_amount');

            $financeOut = DB::connection('legacy_new')
                ->table('report_cashflow_monthly')
                ->where('tenant_id', $this->tenantId)
                ->where('company_id', $this->companyId)
                ->where('year_month', $yearMonth)
                ->where('section', 'FINANCING')
                ->where('direction', 'OUT')
                ->sum('total_amount');

            $operatingProfit = $revenue - $expenses;

            // Update main PnL table
            DB::connection('legacy_new')->table('report_pnl_monthly')->upsert([
                'tenant_id' => $this->tenantId,
                'company_id' => $this->companyId,
                'year_month' => $yearMonth,
                'revenue_operating' => $revenue,
                'expense_operating' => $expenses,
                'operating_profit' => $operatingProfit,
                'finance_in' => $financeIn,
                'finance_out' => $financeOut,
                'updated_at' => now(),
            ], ['tenant_id', 'company_id', 'year_month']);

            // Update detailed by-item table
            $items = DB::connection('legacy_new')
                ->table('report_cashflow_monthly')
                ->select('cashflow_item_id', 'cashflow_item_name', 'direction', 'total_amount')
                ->where('tenant_id', $this->tenantId)
                ->where('company_id', $this->companyId)
                ->where('year_month', $yearMonth)
                ->where('section', 'OPERATING')
                ->get();

            foreach ($items as $item) {
                DB::connection('legacy_new')->table('report_pnl_monthly_by_item')->upsert([
                    'tenant_id' => $this->tenantId,
                    'company_id' => $this->companyId,
                    'year_month' => $yearMonth,
                    'cashflow_item_id' => $item->cashflow_item_id,
                    'cashflow_item_name' => $item->cashflow_item_name,
                    'direction' => $item->direction,
                    'total_amount' => $item->total_amount,
                    'updated_at' => now(),
                ], ['tenant_id', 'company_id', 'year_month', 'cashflow_item_id']);
            }

            return [
                'success' => true,
                'month' => $yearMonth,
                'revenue' => $revenue,
                'expenses' => $expenses,
                'profit' => $operatingProfit,
            ];
        });
    }

    /**
     * Take daily snapshot of debts (AR/AP)
     */
    public function snapshotDebts(string $dateYmd): array
    {
        return DB::connection('legacy_new')->transaction(function () use ($dateYmd) {
            $count = 0;

            // Clear old snapshot for the date to avoid stale rows when debts are repaid.
            DB::connection('legacy_new')
                ->table('report_debts_daily')
                ->where('tenant_id', $this->tenantId)
                ->where('company_id', $this->companyId)
                ->where('snapshot_date', $dateYmd)
                ->where('type', 'AR')
                ->delete();

            // AR: Accounts Receivable (contracts with debt)
            $receivables = DB::connection('legacy_new')
                ->table('contracts as c')
                ->selectRaw('
                    c.id as entity_id,
                    CONCAT(cp.name, " - ", c.title) as entity_title,
                    c.total_amount,
                    COALESCE(c.paid_amount, 0) as paid_amount,
                    COALESCE(c.total_amount, 0) - COALESCE(c.paid_amount, 0) as debt_amount,
                    c.contract_date
                ')
                ->leftJoin('counterparties as cp', 'c.counterparty_id', '=', 'cp.id')
                ->where('c.tenant_id', $this->tenantId)
                ->where('c.company_id', $this->companyId)
                ->whereRaw('COALESCE(c.total_amount, 0) - COALESCE(c.paid_amount, 0) > 0')
                ->get();

            foreach ($receivables as $row) {
                DB::connection('legacy_new')->table('report_debts_daily')->upsert([
                    'tenant_id' => $this->tenantId,
                    'company_id' => $this->companyId,
                    'snapshot_date' => $dateYmd,
                    'type' => 'AR',
                    'entity_id' => $row->entity_id,
                    'entity_title' => $row->entity_title,
                    'amount_total' => $row->total_amount,
                    'amount_paid' => $row->paid_amount,
                    'amount_debt' => $row->debt_amount,
                    'days_overdue' => $this->calculateDaysOverdue($dateYmd, $row->contract_date),
                    'updated_at' => now(),
                ], ['tenant_id', 'company_id', 'snapshot_date', 'type', 'entity_id']);
                $count++;
            }

            // AP: Accounts Payable (spendings without transactions or unpaid transactions) - MVP
            // Simple rule: spendings with transaction_id = null OR is_paid = 0
            // (Full AP logic would involve vendor payables - future enhancement)

            return ['success' => true, 'date' => $dateYmd, 'ar_records' => $count];
        });
    }

    /**
     * Reconcile month: compare transactions sum vs report tables
     */
    public function reconcileMonth(string $yearMonth): array
    {
        $issues = [];

        // Issue 1: Paid transactions without cashflow_item_id
        $orphanedQuery = DB::connection('legacy_new')
            ->table('transactions')
            ->where('is_paid', 1)
            ->whereNull('cashflow_item_id')
            ->where('tenant_id', $this->tenantId)
            ->where('company_id', $this->companyId);

        $this->applyYearMonthFilter($orphanedQuery, 'date_is_paid', $yearMonth);
        $orphaned = $orphanedQuery->count();

        if ($orphaned > 0) {
            $issues[] = "Paid transactions without cashflow_item_id: $orphaned";
        }

        // Issue 2: Transactions with null date_is_paid but is_paid=1
        $nullDates = DB::connection('legacy_new')
            ->table('transactions')
            ->where('is_paid', 1)
            ->whereNull('date_is_paid')
            ->where('tenant_id', $this->tenantId)
            ->where('company_id', $this->companyId)
            ->count();

        if ($nullDates > 0) {
            $issues[] = "Paid transactions with null date_is_paid: $nullDates";
        }

        // Issue 3: Cash transfers included in reports (they shouldn't be)
        $transfersQuery = DB::connection('legacy_new')
            ->table('transactions as t')
            ->whereNotIn('t.id', function ($q) {
                $q->select('transaction_in_id')->from('cash_transfers')
                    ->where('tenant_id', $this->tenantId)
                    ->where('company_id', $this->companyId)
                    ->whereNotNull('transaction_in_id');
            })
            ->whereNotIn('t.id', function ($q) {
                $q->select('transaction_out_id')->from('cash_transfers')
                    ->where('tenant_id', $this->tenantId)
                    ->where('company_id', $this->companyId)
                    ->whereNotNull('transaction_out_id');
            })
            ->where('is_paid', 1)
            ->where('t.tenant_id', $this->tenantId)
            ->where('t.company_id', $this->companyId);

        $this->applyYearMonthFilter($transfersQuery, 't.date_is_paid', $yearMonth);
        $transfers = $transfersQuery->count();

        if ($transfers > 0) {
            $issues[] = "Cash transfers not properly excluded: $transfers";
        }

        return [
            'month' => $yearMonth,
            'valid' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Get period status (OPEN/CLOSED)
     */
    protected function getPeriodStatus(string $yearMonth)
    {
        return DB::connection('legacy_new')
            ->table('finance_periods')
            ->where('tenant_id', $this->tenantId)
            ->where('company_id', $this->companyId)
            ->where('year_month', $yearMonth)
            ->first();
    }

    private function applyYearMonthFilter($query, string $column, string $yearMonth): void
    {
        $driver = DB::connection('legacy_new')->getDriverName();
        $monthExpression = match ($driver) {
            'pgsql' => "to_char($column, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', $column)",
            default => "DATE_FORMAT($column, '%Y-%m')",
        };

        $query->whereRaw("$monthExpression = ?", [$yearMonth]);
    }

    private function calculateDaysOverdue(string $dateYmd, $contractDate): int
    {
        if (!$contractDate) {
            return 0;
        }

        $end = Carbon::parse($dateYmd)->startOfDay();
        $start = Carbon::parse((string) $contractDate)->startOfDay();

        return max(0, $start->diffInDays($end, false));
    }
}
