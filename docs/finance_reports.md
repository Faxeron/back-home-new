# Financial Analytics Core — SQL Views Documentation

**REALITY STATUS**: ✅ IMPLEMENTED (2026-02-10)

## Overview

The financial analytics core consists of 4 SQL VIEWs that provide real-time aggregated data for financial reporting, dashboards, and ApexCharts integration. All views are read-only and pull data only from paid transactions (`is_paid = 1`).

**Key Principles:**
- Always source from truth: `transactions` table (filtered by `is_paid = 1`)
- Never duplicate money data
- Exclude internal transfers (`cash_transfers`)
- All dates based on `date_is_paid`
- Grouped by company for multi-tenant isolation

---

## 1️⃣ VIEW: report_cashflow_monthly (ОДДС)

**Purpose**: Monthly cash flow statement following accounting standards (Отчёт о движении денежных средств)

### Structure

| Column | Type | Description |
|--------|------|-------------|
| `year` | INT | Year (e.g., 2026) |
| `month` | INT | Month (1-12) |
| `company_id` | BIGINT | Company identifier |
| `section` | ENUM | OPERATING, INVESTING, FINANCING |
| `direction` | ENUM | IN (inflows) or OUT (outflows) |
| `cashflow_item_id` | BIGINT | Reference to cashflow_items table |
| `cashflow_item_code` | VARCHAR | Code (e.g., 'OP_IN_CLIENT_PAYMENT') |
| `cashflow_item_name` | VARCHAR | Human-readable name |
| `total_amount` | DECIMAL(15,2) | Sum of transaction amounts for month |

### Logic

```sql
SOURCE: transactions t
  LEFT JOIN cashflow_items c ON t.cashflow_item_id = c.id
  
WHERE:
  t.is_paid = 1
  AND t.cashflow_item_id IS NOT NULL
  AND t.date_is_paid IS NOT NULL
  
GROUP BY: company_id, section, direction, cashflow_item_code, year, month
AGGREGATE: SUM(t.sum)
```

### Example Data

```
year | month | company_id | section | direction | item_name | total_amount
-----|-------|------------|---------|-----------|-----------|---------------
2026 |  01   | 1          | OPERATING | IN   | Оплата клиентов | 4,500,000
2026 |  01   | 1          | OPERATING | OUT  | Реклама | 420,000
2026 |  01   | 1          | OPERATING | OUT  | Зарплата | 1,200,000
2026 |  01   | 1          | INVESTING | OUT  | Покупка оборудования | 850,000
```

### Usage in ApexCharts

```javascript
// Stacked area chart: Revenue vs Expenses over months
const monthlyData = await api.get('/api/reports/cashflow-monthly');

const inflows = monthlyData
  .filter(row => row.direction === 'IN')
  .reduce((acc, row) => {
    acc[row.year_month] = (acc[row.year_month] || 0) + row.total_amount;
    return acc;
  }, {});

const outflows = monthlyData
  .filter(row => row.direction === 'OUT')
  .reduce((acc, row) => {
    acc[row.year_month] = (acc[row.year_month] || 0) + row.total_amount;
    return acc;
  }, {});

// ApexChart series:
// Series 1: Inflows (IN direction)
// Series 2: Outflows (OUT direction)
// X-axis: year_month values (2026-01, 2026-02, etc.)
```

---

## 2️⃣ VIEW: report_pnl_monthly (ОПУ)

**Purpose**: Monthly Profit & Loss statement (Отчёт о прибылях и убытках)

### Structure

| Column | Type | Description |
|--------|------|-------------|
| `year` | INT | Year (e.g., 2026) |
| `month` | INT | Month (1-12) |
| `company_id` | BIGINT | Company identifier |
| `revenue_total` | DECIMAL(15,2) | All OPERATING IN flows |
| `expense_operating` | DECIMAL(15,2) | All OPERATING OUT flows |
| `expense_ads` | DECIMAL(15,2) | Advertising expenses (OP_OUT_ADVERTISING) |
| `expense_salary` | DECIMAL(15,2) | Salary/bonuses (OP_OUT_SALARY) |
| `expense_other` | DECIMAL(15,2) | Other OPERATING expenses |
| `net_profit` | DECIMAL(15,2) | revenue_total - expense_operating |

### Logic

```sql
SOURCE: transactions t
  LEFT JOIN cashflow_items c ON t.cashflow_item_id = c.id

WHERE:
  t.is_paid = 1
  AND t.date_is_paid IS NOT NULL
  
COMPUTE:
  revenue_total = SUM(amount WHERE c.section='OPERATING' AND c.direction='IN')
  expense_operating = SUM(amount WHERE c.section='OPERATING' AND c.direction='OUT')
  expense_ads = SUM(amount WHERE c.code='OP_OUT_ADVERTISING')
  expense_salary = SUM(amount WHERE c.code='OP_OUT_SALARY')
  expense_other = expense_operating - expense_ads - expense_salary
  net_profit = revenue_total - expense_operating

GROUP BY: company_id, year, month
```

### Example Data

```
year | month | company_id | revenue_total | expense_operating | expense_ads | expense_salary | net_profit
-----|-------|------------|---------------|-------------------|-------------|----------------|----------
2026 |  01   | 1          | 4,500,000     | 2,470,000        | 420,000    | 1,200,000     | 2,030,000
```

### Usage in ApexCharts

```javascript
// Line chart: Net Profit trend
const pnlData = await api.get('/api/reports/pnl-monthly');

const profitTrend = pnlData.map(row => ({
  month: `${row.year}-${String(row.month).padStart(2, '0')}`,
  profit: row.net_profit,
  revenue: row.revenue_total,
  expenses: row.expense_operating,
}));

// ApexChart series:
// Series 1: Revenue (line)
// Series 2: Expenses (line)
// Series 3: Net Profit (column chart overlay)
```

---

## 3️⃣ VIEW: report_debts (ДКЗ)

**Purpose**: Receivables and Payables aging (Дебиторская и кредиторская задолженность)

### Structure

| Column | Type | Description |
|--------|------|-------------|
| `contract_id` | BIGINT | Contract ID |
| `company_id` | BIGINT | Company identifier |
| `client_name` | VARCHAR | Client/counterparty name |
| `total_amount` | DECIMAL(14,2) | Contract total amount |
| `paid_amount` | DECIMAL(14,2) | Amount already paid |
| `debt_amount` | DECIMAL(14,2) | Remaining unpaid (total - paid) |
| `contract_date` | DATE | Contract date |
| `status` | VARCHAR | Contract status (e.g., 'Active', 'Completed') |
| `days_overdue` | INT | Days since contract_date (for aging analysis) |

### Logic

```sql
SOURCE: contracts c
  LEFT JOIN counterparties cp ON c.counterparty_id = cp.id
  LEFT JOIN contract_statuses cs ON c.contract_status_id = cs.id

WHERE:
  (c.total_amount - c.paid_amount) > 0
  AND c.contract_date IS NOT NULL

COMPUTE:
  debt_amount = COALESCE(c.total_amount, 0) - COALESCE(c.paid_amount, 0)
  days_overdue = DATEDIFF(CURDATE(), c.contract_date)
```

### Example Data

```
contract_id | client_name | total_amount | paid_amount | debt_amount | days_overdue
------------|-------------|--------------|-------------|-------------|-------------
1           | ООО Альфа   | 500,000     | 300,000     | 200,000     | 45
2           | ИП Бета     | 150,000     | 0           | 150,000     | 120
3           | ООО Гамма   | 250,000     | 125,000     | 125,000     | 15
```

### Usage in ApexCharts

```javascript
// Pie chart: Debt breakdown by client
// or Age bucket analysis: <30 days, 30-60, 60-90, >90 days

const debtData = await api.get('/api/reports/debts');

const ageBuckets = {
  current: debtData.filter(d => d.days_overdue < 30).reduce((s, d) => s + d.debt_amount, 0),
  overdue30: debtData.filter(d => d.days_overdue >= 30 && d.days_overdue < 60).reduce((s, d) => s + d.debt_amount, 0),
  overdue60: debtData.filter(d => d.days_overdue >= 60 && d.days_overdue < 90).reduce((s, d) => s + d.debt_amount, 0),
  overdue90: debtData.filter(d => d.days_overdue >= 90).reduce((s, d) => s + d.debt_amount, 0),
};

// ApexChart series:
// Pie slices: [ageBuckets.current, ageBuckets.overdue30, ageBuckets.overdue60, ageBuckets.overdue90]
// Labels: ['Current', '30-60 days', '60-90 days', '90+ days']
```

---

## 4️⃣ VIEW: report_cashbox_balance

**Purpose**: Real-time cash box balance (Кассовый остаток)

### Structure

| Column | Type | Description |
|--------|------|-------------|
| `company_id` | BIGINT | Company identifier |
| `cashbox_id` | BIGINT | Cashbox ID |
| `cashbox_name` | VARCHAR | Cashbox name (e.g., 'Main Account', 'Petty Cash') |
| `balance_now` | DECIMAL(15,2) | Current balance = SUM(IN) - SUM(OUT) |

### Logic

```sql
SOURCE: cashboxes cb
  LEFT JOIN transactions t ON cb.id = t.cashbox_id
    AND t.is_paid = 1
    AND t.date_is_paid IS NOT NULL
  LEFT JOIN cashflow_items ci ON t.cashflow_item_id = ci.id

WHERE:
  cb.is_active = 1

COMPUTE:
  balance_now = SUM(
    CASE
      WHEN ci.direction = 'IN' THEN t.sum
      WHEN ci.direction = 'OUT' THEN -t.sum
      ELSE 0
    END
  )

GROUP BY: company_id, cashbox_id, cb.name
```

### Example Data

```
company_id | cashbox_id | cashbox_name | balance_now
-----------|------------|--------------|-------------
1          | 1          | Main Account | 2,450,000
1          | 2          | Bank Account | 5,200,000
1          | 3          | Petty Cash   | 85,000
```

### Usage in ApexCharts

```javascript
// Gauge chart or radial bar: Current balances per cashbox
const balanceData = await api.get('/api/reports/cashbox-balance');

balanceData.forEach(box => {
  // Display gauge: box.cashbox_name → box.balance_now
  // Update in real-time as transactions are paid
});
```

---

## Database Indexes

All VIEWs leverage existing indexes on `transactions`:

| Index Name | Columns | Purpose |
|------------|---------|---------|
| `transactions_paid_date_idx` | `(tenant_id, company_id, is_paid, date_is_paid)` | Core filter for all VIEWs |
| `transactions_cashflow_item_id_fk` | `(cashflow_item_id)` | JOIN with cashflow_items |
| `transactions_company_cashbox_completed_idx` | `(company_id, cashbox_id, is_completed)` | Multi-tenant & cashbox queries |
| `transactions_cashbox_paid_date_idx` | `(cashbox_id, is_paid, date_is_paid)` | Cashbox balance calculations |

---

## API Endpoints (Planned)

```
GET /api/reports/cashflow-monthly?from=2026-01-01&to=2026-12-31&company_id=1
GET /api/reports/pnl-monthly?year=2026&company_id=1
GET /api/reports/debts?company_id=1&order_by=days_overdue
GET /api/reports/cashbox-balance?company_id=1
```

---

## Performance Notes

- **report_cashflow_monthly**: Fast (indexed by date_is_paid, company_id)
- **report_pnl_monthly**: Fast (simple aggregation by year/month)
- **report_debts**: Fast (minimal JOINs, filtered by debt > 0)
- **report_cashbox_balance**: Very Fast (simple SUM by cashbox)

All VIEWs are optimized for ApexCharts real-time updates. For large datasets (>1M transactions), consider incremental materialized views or separate analytics tables.

---

## Migration Info

**File**: `database/migrations_new/2026_02_10_000003_create_finance_views.php`

To recreate or modify:
```bash
php artisan migrate:fresh --path=database/migrations_new --database=legacy_new
```

To rollback only views:
```bash
php artisan migrate:rollback --step=1 --database=legacy_new
```

---

## Related Files

- [AGENTS.md](../AGENTS.md) — Architecture guidelines
- [Finance Module.md](./Finance%20Module.md) — Finance module structure
- [PRICING_REWORK_MASTER_PLAN.md](./PRICING_REWORK_MASTER_PLAN.md) — Pricing engine integration
