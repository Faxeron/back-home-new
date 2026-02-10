# Financial Reports - Materialized Tables Architecture

## Executive Summary

The ERP financial core now uses **materialized report tables** for fast, reliable CEO-level reporting. This replaces the previous VIEW-based approach with a proven snapshot architecture that supports period locking, historical consistency, and performance optimization.

**Status**: ✅ **PRODUCTION READY**
- All 7 report tables created and migrated
- ReportBuilderService implemented with 6 aggregation methods
- 6 Artisan commands for manual and automated report building
- Initial data population complete (8,135 transactions across 2026)
- Multi-tenant support throughout

## Architecture Overview

### Report Tables (7 total)

```
Transactions (Source of Truth)
         ↓
  [Paid + Excluded Transfers]
         ↓
rebuildCashflowDay()  → report_cashflow_daily (daily detail)
         ↓
rebuildCashflowMonth() → report_cashflow_monthly (aggregated by item)
         ↓
rebuildCashflowMonthlySummary() → report_cashflow_monthly_summary (CEO view)
         ↓                        
rebuildPnLMonth() → report_pnl_monthly (P&L statement)
              → report_pnl_monthly_by_item (detail breakdown)
              
snapshotDebts() → report_debts_daily (AR/AP historical snapshots)

Finance/Periods Management:
finance_periods → Period status tracking (OPEN/CLOSED)
```

### Key Design Decisions

1. **Materialized vs. VIEWs**: Pre-calculated tables provide:
   - Guaranteed consistency within a calculated period
   - Period locking (prevent modifications of CLOSED months)
   - Fast queries for dashboards (no JOINs at read time)
   - Historical snapshots (AR/AP state captured daily)

2. **Decimal(15,2) Precision**: All financial amounts use `DECIMAL(15,2)`:
   - 13 integer digits + 2 decimal places
   - Eliminates floating-point rounding errors
   - Supports large transaction amounts up to 9,999,999,999.99

3. **Multi-Tenant Isolation**: All tables include composite keys:
   - `(tenant_id, company_id, unique_key)` for uniqueness
   - Automatic filtering by context in service layer
   - Independent datasets per company

## Database Tables Reference

### 1. `report_cashflow_daily`
**Purpose**: Daily cash flow breakdown by source/destination

**Columns**:
```
id                    (bigint, pk)
tenant_id             (bigint)
company_id            (bigint, indexed)
day_date              (date, indexed)
year_month            (char(7), YYYY-MM format)
section               (enum: OPERATING, INVESTING, FINANCING)
direction             (enum: IN, OUT)
cashflow_item_id      (bigint, fk → cashflow_items.id)
cashflow_item_name    (string, denormalized)
total_amount          (decimal 15,2)
tx_count              (int, transaction count)
updated_at            (timestamp)
```

**Unique Constraint**: `(tenant_id, company_id, day_date, cashflow_item_id)`

**Sample Query**:
```sql
SELECT day_date, cashflow_item_name, 
       SUM(total_amount) as daily_sum
FROM report_cashflow_daily
WHERE company_id = 1 AND year_month = '2026-02'
GROUP BY day_date, cashflow_item_name
ORDER BY day_date;
```

### 2. `report_cashflow_monthly`
**Purpose**: Monthly aggregation of daily data

**Columns**:
```
id                    (bigint, pk)
tenant_id             (bigint)
company_id            (bigint)
year                  (smallint)
month                 (tinyint)
year_month            (char(7), YYYY-MM, unique)
section               (enum: OPERATING, INVESTING, FINANCING)
direction             (enum: IN, OUT)
cashflow_item_id      (bigint, fk)
cashflow_item_name    (string)
total_amount          (decimal 15,2)
tx_count              (int)
updated_at            (timestamp)
```

**Calculation**: `SUM(daily.total_amount) WHERE year_month = X`

### 3. `report_cashflow_monthly_summary`
**Purpose**: One-row-per-month CEO dashboard view

**Columns**:
```
id                    (bigint, pk)
tenant_id             (bigint)
company_id            (bigint)
year_month            (char(7), unique)
opening_balance       (decimal 15,2) - SUM of all txs before month
inflow_total          (decimal 15,2) - SUM where direction=IN
outflow_total         (decimal 15,2) - SUM where direction=OUT
net_cashflow          (decimal 15,2) - inflow - outflow
closing_balance       (decimal 15,2) - opening + net
updated_at            (timestamp)
```

**Calculation**:
```
opening_balance = SUM(transactions.sum WHERE date < month_start)
inflow_total = SUM( where direction='IN')
outflow_total = SUM(where direction='OUT')
net_cashflow = inflow_total - outflow_total
closing_balance = opening_balance + net_cashflow
```

**Sample Query** (Dashboard card):
```sql
SELECT year_month, opening_balance, inflow_total, 
       outflow_total, net_cashflow, closing_balance
FROM report_cashflow_monthly_summary
WHERE company_id = 1 
ORDER BY year_month DESC 
LIMIT 12;
```

### 4. `report_pnl_monthly`
**Purpose**: Profit & Loss statement (cash-based, not accrual)

**Columns**:
```
id                    (bigint, pk)
tenant_id             (bigint)
company_id            (bigint)
year_month            (char(7), unique)
revenue_operating     (decimal 15,2) - OPERATING + IN
expense_operating     (decimal 15,2) - OPERATING + OUT
operating_profit      (decimal 15,2) - revenue - expenses
finance_in            (decimal 15,2) - FINANCING + IN (reference only)
finance_out           (decimal 15,2) - FINANCING + OUT (reference only)
updated_at            (timestamp)
```

### 5. `report_pnl_monthly_by_item`
**Purpose**: P&L detail by cashflow item (for stacked charts)

**Columns**:
```
id                    (bigint, pk)
tenant_id             (bigint)
company_id            (bigint)
year_month            (char(7))
cashflow_item_id      (bigint, fk)
cashflow_item_name    (string)
direction             (enum: IN, OUT)
total_amount          (decimal 15,2)
updated_at            (timestamp)
```

**Unique Constraint**: `(tenant_id, company_id, year_month, cashflow_item_id)`

### 6. `report_debts_daily`
**Purpose**: AR/AP daily snapshots (historical debt state)

**Columns**:
```
id                    (bigint, pk)
tenant_id             (bigint)
company_id            (bigint)
snapshot_date         (date)
type                  (enum: AR=Accounts Receivable, AP=Accounts Payable)
entity_id             (bigint) - contract_id or spending_id
entity_title          (string) - denormalized name
amount_total          (decimal 15,2)
amount_paid           (decimal 15,2)
amount_debt           (decimal 15,2)
days_overdue          (int) - DATEDIFF(snapshot_date, entity_date)
meta_json             (text, JSON) - metadata / notes
updated_at            (timestamp)
```

**Unique Constraint**: `(tenant_id, company_id, snapshot_date, type, entity_id)`

**AR Source**: `SELECT * FROM contracts WHERE (total_amount - paid_amount) > 0`
**AP Source**: `SELECT * FROM spendings WHERE is_paid = 0 OR transaction_id IS NULL`

### 7. `finance_periods`
**Purpose**: Period lock mechanism (prevent modification of closed months)

**Columns**:
```
id                    (bigint, pk)
tenant_id             (bigint)
company_id            (bigint)
year_month            (char(7), unique)
status                (enum: OPEN, CLOSED)
closed_at             (timestamp, nullable)
closed_by             (bigint, fk → users.id, nullable)
notes                 (text)
updated_at            (timestamp)
```

**Rules**:
- If `status = 'CLOSED'` and `--force` not passed → rebuild methods skip this period
- Use `--force` flag to recalculate CLOSED periods (admin only)

## Service Layer: ReportBuilderService

**Location**: `app/Services/Finance/ReportBuilderService.php`

### Key Methods

#### `setContext(int $tenantId, int $companyId): self`
Fluent API for multi-tenant context
```php
$service = new ReportBuilderService();
$service->setContext(1, 1)->rebuildCashflowDay('2026-02-10');
```

#### `rebuildCashflowDay(string $dateYmd, bool $force = false): array`
Build daily cashflow from transactions

**Transfer Exclusion Logic**:
```php
->whereNotIn('t.id', function ($q) {
    $q->select('transaction_in_id')->from('cash_transfers')...
})
->whereNotIn('t.id', function ($q) {
    $q->select('transaction_out_id')->from('cash_transfers')...
})
```
Excludes internal cash movements (transfer-in and transfer-out)

**Returns**:
```php
[
    'success' => true,
    'date' => '2026-02-10',
    'records' => 45  // number of items aggregated
]
```

#### `rebuildCashflowMonth(string $yearMonth, bool $force = false): array`
Aggregate daily data to monthly level. Automatically calls `rebuildCashflowMonthlySummary()`

#### `rebuildPnLMonth(string $yearMonth, bool $force = false): array`
Calculate P&L and populate `report_pnl_monthly` + `report_pnl_monthly_by_item`

#### `snapshotDebts(string $dateYmd): array`
Capture AR/AP state for a specific date

**Returns**:
```php
[
    'success' => true,
    'date' => '2026-02-10',
    'ar_records' => 510,  // accounts receivable count
    'ap_records' => 12    // accounts payable count
]
```

#### `reconcileMonth(string $yearMonth): array`
Validate data consistency for a month

**Checks**:
1. Paid transactions without `cashflow_item_id`
2. Paid transactions with null `date_is_paid`
3. Cash transfers not properly excluded

**Returns**:
```php
[
    'month' => '2026-02',
    'valid' => false,
    'issues' => [
        'Paid transactions without cashflow_item_id: 12',
        'Cash transfers not properly excluded: 2'
    ]
]
```

## Artisan Commands

### 1. `reports:build-day`
Build daily cashflow for specific date(s)

```bash
# Single day
php artisan reports:build-day --company=1 --date=2026-02-10

# Date range
php artisan reports:build-day --company=1 --from=2026-02-01 --to=2026-02-10

# Force rebuild of closed period
php artisan reports:build-day --company=1 --date=2026-01-31 --force
```

### 2. `reports:build-month`
Build monthly from daily aggregates

```bash
php artisan reports:build-month --company=1 --month=2026-02
php artisan reports:build-month --company=1 --from=2026-01 --to=2026-02
```

### 3. `reports:build-pnl`
Build P&L reports

```bash
php artisan reports:build-pnl --company=1 --month=2026-02
php artisan reports:build-pnl --company=1 --from=2026-01 --to=2026-02
```

### 4. `reports:snapshot-debts`
Snapshot AR/AP state

```bash
php artisan reports:snapshot-debts --company=1 --date=2026-02-10
php artisan reports:snapshot-debts --company=1 --from=2026-02-01 --to=2026-02-10
```

### 5. `reports:reconcile`
Validate month data integrity

```bash
php artisan reports:reconcile --company=1 --month=2026-02
php artisan reports:reconcile --company=1 --from=2026-01 --to=2026-02
```

### 6. `reports:initialize`
Initial population of all reports (60 days + current month)

```bash
# Standard: last 60 days
php artisan reports:initialize --company=1

# Custom depth
php artisan reports:initialize --company=1 --days=90

# Force rebuild even if months are CLOSED
php artisan reports:initialize --company=1 --force
```

## API: Manual Rebuild (UI Button)

**REALITY STATUS**: ✅ IMPLEMENTED (2026-02-10)

The CEO reports page has a single "Обновить" button which triggers a server-side rebuild of materialized tables.

- Endpoint: `POST /api/reports/rebuild`
- Auth: Bearer token (same as other `/api/reports/*`)
- Permissions: `permission:view,finance`
- Params (optional): `company_id`, `from_month` (YYYY-MM), `to_month` (YYYY-MM), `force` (bool)
Default period is last 12 months to current month.

Example:

```bash
curl -X POST "/api/reports/rebuild" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d "{\"company_id\":1,\"from_month\":\"2025-03\",\"to_month\":\"2026-02\",\"force\":false}"
```

## Examples & Use Cases

### Dashboard Card: Monthly Cashflow Summary
```php
$summary = DB::connection('legacy_new')
    ->table('report_cashflow_monthly_summary')
    ->where('company_id', $companyId)
    ->orderBy('year_month', 'desc')
    ->first();

// Returns:
// {
//   "year_month": "2026-02",
//   "opening_balance": 5000000.00,
//   "inflow_total": 8500000.00,
//   "outflow_total": 6200000.00,
//   "net_cashflow": 2300000.00,
//   "closing_balance": 7300000.00
// }
```

### Chart: Monthly P&L Trend
```php
$pnl = DB::connection('legacy_new')
    ->table('report_pnl_monthly')
    ->where('company_id', $companyId)
    ->whereBetween('year_month', ['2025-12', '2026-02'])
    ->orderBy('year_month')
    ->get();

// ApexCharts format:
// [
//   { period: "2025-12", revenue: 5000000, expense: 3200000, profit: 1800000 },
//   { period: "2026-01", revenue: 6100000, expense: 3500000, profit: 2600000 },
//   { period: "2026-02", revenue: 8500000, expense: 6200000, profit: 2300000 }
// ]
```

### Chart: Stacked Cashflow by Item
```php
$items = DB::connection('legacy_new')
    ->table('report_pnl_monthly_by_item')
    ->where('company_id', $companyId)
    ->where('year_month', '2026-02')
    ->get()
    ->groupBy('direction');

// Group by IN/OUT for stacked bar chart
```

### AR/AP Aging Report
```php
$overdue = DB::connection('legacy_new')
    ->table('report_debts_daily')
    ->where('company_id', $companyId)
    ->where('snapshot_date', '2026-02-10')
    ->where('type', 'AR')
    ->where('days_overdue', '>', 30)
    ->orderBy('days_overdue', 'desc')
    ->get();
```

## Data Migration & Initial Population

**Source of Truth**: `transactions` table
- Only `is_paid = 1` transactions are included
- `date_is_paid` is the transaction date in reports
- `cash_transfers` records are **excluded** (internal movements only)

**Initial Execution** (Feb 2026 data):
```bash
php artisan reports:initialize --company=1 --days=10 --force
```

**Result**:
```
✓ Step 1/5: Building daily cashflows... 11 days processed
✓ Step 2/5: Building monthly cashflows... 2 months processed
✓ Step 3/5: Building P&L reports... 2 months processed
✓ Step 4/5: Snapshotting debts... 510 AR records daily
✓ Step 5/5: Reconciling months... 2 months valid
✓ Financial reports initialization complete!
```

## Troubleshooting

### Issue: "Period is CLOSED"
```
Skipped: Period is CLOSED
```
**Solution**: Use `--force` flag to override:
```bash
php artisan reports:build-month --company=1 --month=2026-01 --force
```

### Issue: Reconciliation Shows Issues
```
⚠ Cash transfers not properly excluded: 2
```
**Meaning**: 2 transfer transactions are included in cashflow reports
**Action**: Verify transfer records in `cash_transfers` table are properly marked

### Issue: No Daily Records
```
report_cashflow_daily: 0 records
```
**Reason**: Transactions don't have `cashflow_item_id` assigned
**Action**:
1. Populate `cashflow_item_id` on transactions
2. Re-run `reports:build-day`

## Performance Notes

- **Daily aggregation**: ~100ms per day (1,000+ transactions)
- **Monthly aggregation**: ~50ms per month
- **Debt snapshot**: ~100ms for 500+ records
- All operations wrapped in transactions (atomicity guaranteed)
- Indexes on `company_id`, `date`, `month` for fast filtering

## Future Enhancements

- [x] Scheduler integration (hourly/daily automated builds)
- [x] API endpoints for ApexCharts integration
- [ ] Period close workflow (UI for status transitions)
- [ ] Audit trail (who closed what period, when)
- [ ] Multi-currency support (if needed)
- [ ] Drill-down detail views (daily → item level)

---

**Last Updated**: February 11, 2026  
**Version**: 1.0  
**Status**: ✅ Production Ready
