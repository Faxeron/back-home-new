<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'legacy_new';

    public function up(): void
    {
        // VIEW 1: report_cashflow_monthly (ОДДС) - simplified
        DB::unprepared('DROP VIEW IF EXISTS report_cashflow_monthly');
        DB::unprepared("CREATE VIEW report_cashflow_monthly AS SELECT YEAR(t.date_is_paid) AS `year`, MONTH(t.date_is_paid) AS `month`, t.company_id, c.section, c.direction, t.cashflow_item_id, c.code AS cashflow_item_code, c.name AS cashflow_item_name, SUM(t.sum) AS total_amount FROM transactions t LEFT JOIN cashflow_items c ON t.cashflow_item_id = c.id WHERE t.is_paid = 1 AND t.cashflow_item_id IS NOT NULL AND t.date_is_paid IS NOT NULL GROUP BY t.company_id, c.section, c.direction, t.cashflow_item_id, c.code, c.name, YEAR(t.date_is_paid), MONTH(t.date_is_paid)");

        // VIEW 2: report_pnl_monthly (ОПУ)
        DB::unprepared('DROP VIEW IF EXISTS report_pnl_monthly');
        DB::unprepared("CREATE VIEW report_pnl_monthly AS SELECT YEAR(t.date_is_paid) AS `year`, MONTH(t.date_is_paid) AS `month`, t.company_id, SUM(IF(c.section = 'OPERATING' AND c.direction = 'IN', t.sum, 0)) AS revenue_total, SUM(IF(c.section = 'OPERATING' AND c.direction = 'OUT', t.sum, 0)) AS expense_operating, SUM(IF(c.code = 'OP_OUT_ADVERTISING' AND c.direction = 'OUT', t.sum, 0)) AS expense_ads, SUM(IF(c.code = 'OP_OUT_SALARY' AND c.direction = 'OUT', t.sum, 0)) AS expense_salary, SUM(IF(c.section = 'OPERATING' AND c.direction = 'OUT' AND c.code NOT IN ('OP_OUT_ADVERTISING', 'OP_OUT_SALARY'), t.sum, 0)) AS expense_other, SUM(IF(c.section = 'OPERATING' AND c.direction = 'IN', t.sum, 0)) - SUM(IF(c.section = 'OPERATING' AND c.direction = 'OUT', t.sum, 0)) AS net_profit FROM transactions t LEFT JOIN cashflow_items c ON t.cashflow_item_id = c.id WHERE t.is_paid = 1 AND t.date_is_paid IS NOT NULL GROUP BY t.company_id, YEAR(t.date_is_paid), MONTH(t.date_is_paid)");

        // VIEW 3: report_debts (ДКЗ)
        DB::unprepared('DROP VIEW IF EXISTS report_debts');
        DB::unprepared("CREATE VIEW report_debts AS SELECT c.id AS contract_id, c.company_id, cp.name AS client_name, c.total_amount, COALESCE(c.paid_amount, 0) AS paid_amount, COALESCE(c.total_amount, 0) - COALESCE(c.paid_amount, 0) AS debt_amount, c.contract_date, cs.name AS `status`, DATEDIFF(CURDATE(), c.contract_date) AS days_overdue FROM contracts c LEFT JOIN counterparties cp ON c.counterparty_id = cp.id LEFT JOIN contract_statuses cs ON c.contract_status_id = cs.id WHERE COALESCE(c.total_amount, 0) - COALESCE(c.paid_amount, 0) > 0 AND c.contract_date IS NOT NULL");

        // VIEW 4: report_cashbox_balance
        DB::unprepared('DROP VIEW IF EXISTS report_cashbox_balance');
        DB::unprepared("CREATE VIEW report_cashbox_balance AS SELECT cb.company_id, cb.id AS cashbox_id, cb.name AS cashbox_name, COALESCE(SUM(IF(ci.direction = 'IN', t.sum, IF(ci.direction = 'OUT', -t.sum, 0))), 0) AS balance_now FROM cashboxes cb LEFT JOIN transactions t ON cb.id = t.cashbox_id AND t.is_paid = 1 AND t.date_is_paid IS NOT NULL LEFT JOIN cashflow_items ci ON t.cashflow_item_id = ci.id WHERE cb.is_active = 1 GROUP BY cb.company_id, cb.id, cb.name");
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS report_cashbox_balance');
        DB::unprepared('DROP VIEW IF EXISTS report_debts');
        DB::unprepared('DROP VIEW IF EXISTS report_pnl_monthly');
        DB::unprepared('DROP VIEW IF EXISTS report_cashflow_monthly');
    }
};

