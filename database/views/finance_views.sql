-- ==========================================
-- Financial Reporting Views
-- ==========================================
-- These views provide the backbone for financial analytics:
-- - Cash Flow (ОДДС)
-- - P&L (ОПУ)
-- - Receivables/Payables (ДКЗ)
-- - Cash Box Balance

-- ==========================================
-- VIEW 1: report_cashflow_monthly (ОДДС)
-- ==========================================
-- Monthly cash flow by section and direction
-- Source: transactions WHERE is_paid = 1
-- Excludes: cash_transfers
-- Groups by: year, month, company_id, cashflow_item (section, direction)

DROP VIEW IF EXISTS report_cashflow_monthly;
CREATE VIEW report_cashflow_monthly AS
SELECT
    YEAR(t.date_is_paid) AS `year`,
    MONTH(t.date_is_paid) AS `month`,
    DATE_FORMAT(t.date_is_paid, '%Y-%m') AS year_month,
    t.company_id,
    c.section,
    c.direction,
    t.cashflow_item_id,
    c.code AS cashflow_item_code,
    c.name AS cashflow_item_name,
    SUM(t.sum) AS total_amount
FROM transactions t
LEFT JOIN cashflow_items c ON t.cashflow_item_id = c.id
WHERE t.is_paid = 1
    AND t.cashflow_item_id IS NOT NULL
    AND t.date_is_paid IS NOT NULL
GROUP BY
    YEAR(t.date_is_paid),
    MONTH(t.date_is_paid),
    DATE_FORMAT(t.date_is_paid, '%Y-%m'),
    t.company_id,
    c.section,
    c.direction,
    t.cashflow_item_id,
    c.code,
    c.name;

-- ==========================================
-- VIEW 2: report_pnl_monthly (ОПУ)
-- ==========================================
-- Monthly profit and loss
-- Revenue: OPERATING, direction=IN (excluding FIN_IN_*)
-- Expenses: OPERATING, direction=OUT
-- Net Profit = Revenue - Expenses

DROP VIEW IF EXISTS report_pnl_monthly;
CREATE VIEW report_pnl_monthly AS
SELECT
    YEAR(t.date_is_paid) AS `year`,
    MONTH(t.date_is_paid) AS `month`,
    DATE_FORMAT(t.date_is_paid, '%Y-%m') AS year_month,
    t.company_id,
    -- Revenue
    COALESCE(SUM(CASE
        WHEN c.section = 'OPERATING' AND c.direction = 'IN'
        THEN t.sum
        ELSE 0
    END), 0) AS revenue_total,
    -- Expenses by category
    COALESCE(SUM(CASE
        WHEN c.section = 'OPERATING' AND c.direction = 'OUT'
        THEN t.sum
        ELSE 0
    END), 0) AS expense_operating,
    -- Ads (if separately tracked in cashflow_items)
    COALESCE(SUM(CASE
        WHEN c.code = 'OP_OUT_ADVERTISING' AND c.direction = 'OUT'
        THEN t.sum
        ELSE 0
    END), 0) AS expense_ads,
    -- Salary
    COALESCE(SUM(CASE
        WHEN c.code = 'OP_OUT_SALARY' AND c.direction = 'OUT'
        THEN t.sum
        ELSE 0
    END), 0) AS expense_salary,
    -- Other expenses
    COALESCE(SUM(CASE
        WHEN c.section = 'OPERATING' AND c.direction = 'OUT'
        AND c.code NOT IN ('OP_OUT_ADVERTISING', 'OP_OUT_SALARY')
        THEN t.sum
        ELSE 0
    END), 0) AS expense_other,
    -- Net Profit
    COALESCE(SUM(CASE
        WHEN c.section = 'OPERATING' AND c.direction = 'IN'
        THEN t.sum
        ELSE 0
    END), 0)
    -
    COALESCE(SUM(CASE
        WHEN c.section = 'OPERATING' AND c.direction = 'OUT'
        THEN t.sum
        ELSE 0
    END), 0) AS net_profit
FROM transactions t
LEFT JOIN cashflow_items c ON t.cashflow_item_id = c.id
WHERE t.is_paid = 1
    AND t.date_is_paid IS NOT NULL
GROUP BY
    YEAR(t.date_is_paid),
    MONTH(t.date_is_paid),
    DATE_FORMAT(t.date_is_paid, '%Y-%m'),
    t.company_id;

-- ==========================================
-- VIEW 3: report_debts (ДКЗ)
-- ==========================================
-- Receivables and Payables by contract
-- Filters: debt > 0 (unpaid portion)
-- Calculates: days_overdue based on contract_date

DROP VIEW IF EXISTS report_debts;
CREATE VIEW report_debts AS
SELECT
    c.id AS contract_id,
    c.company_id,
    cp.name AS client_name,
    c.total_amount,
    COALESCE(c.paid_amount, 0) AS paid_amount,
    COALESCE(c.total_amount, 0) - COALESCE(c.paid_amount, 0) AS debt_amount,
    c.contract_date,
    cs.name AS `status`,
    DATEDIFF(CURDATE(), c.contract_date) AS days_overdue
FROM contracts c
LEFT JOIN counterparties cp ON c.counterparty_id = cp.id
LEFT JOIN contract_statuses cs ON c.contract_status_id = cs.id
WHERE COALESCE(c.total_amount, 0) - COALESCE(c.paid_amount, 0) > 0
    AND c.contract_date IS NOT NULL;

-- ==========================================
-- VIEW 4: report_cashbox_balance
-- ==========================================
-- Current balance in each cashbox
-- Sums all paid transactions per cashbox
-- Transactions IN are positive, OUT are negative

DROP VIEW IF EXISTS report_cashbox_balance;
CREATE VIEW report_cashbox_balance AS
SELECT
    cb.company_id,
    cb.id AS cashbox_id,
    cb.name AS cashbox_name,
    COALESCE(SUM(CASE
        WHEN ci.direction = 'IN' THEN t.sum
        WHEN ci.direction = 'OUT' THEN -t.sum
        ELSE 0
    END), 0) AS balance_now
FROM cashboxes cb
LEFT JOIN transactions t ON cb.id = t.cashbox_id
    AND t.is_paid = 1
    AND t.date_is_paid IS NOT NULL
LEFT JOIN cashflow_items ci ON t.cashflow_item_id = ci.id
WHERE cb.is_active = 1
GROUP BY
    cb.company_id,
    cb.id,
    cb.name;
