<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $db = DB::connection('legacy_new');

        $db->statement("
            INSERT INTO finance_allocations (tenant_id, company_id, spending_id, contract_id, amount, kind, comment, created_by, created_at, updated_at)
            SELECT p.tenant_id, p.company_id, i.spending_id, i.contract_id, i.amount, 'payroll', p.comment, p.created_by, p.created_at, p.created_at
            FROM payroll_payout_items i
            JOIN payroll_payouts p ON p.id = i.payout_id
            WHERE i.spending_id IS NOT NULL
              AND i.contract_id IS NOT NULL
              AND NOT EXISTS (
                SELECT 1 FROM finance_allocations fa
                WHERE fa.spending_id = i.spending_id
                  AND fa.contract_id = i.contract_id
                  AND fa.amount = i.amount
                  AND fa.kind = 'payroll'
              )
        ");

        $db->statement("
            INSERT INTO finance_allocations (tenant_id, company_id, spending_id, contract_id, amount, kind, comment, created_by, created_at, updated_at)
            SELECT s.tenant_id, s.company_id, s.id, s.contract_id, ABS(s.sum), 'expense', s.description, s.created_by, s.created_at, s.created_at
            FROM spendings s
            WHERE s.contract_id IS NOT NULL
              AND s.contract_id > 0
              AND NOT EXISTS (
                SELECT 1 FROM finance_allocations fa
                WHERE fa.spending_id = s.id
              )
        ");

        $db->statement("
            INSERT INTO finance_allocations (tenant_id, company_id, receipt_id, contract_id, amount, kind, comment, created_by, created_at, updated_at)
            SELECT r.tenant_id, r.company_id, r.id, r.contract_id, ABS(r.sum), 'income', r.description, r.created_by, r.created_at, r.created_at
            FROM receipts r
            WHERE r.contract_id IS NOT NULL
              AND r.contract_id > 0
              AND NOT EXISTS (
                SELECT 1 FROM finance_allocations fa
                WHERE fa.receipt_id = r.id
              )
        ");
    }

    public function down(): void
    {
        DB::connection('legacy_new')->table('finance_allocations')->delete();
    }
};
