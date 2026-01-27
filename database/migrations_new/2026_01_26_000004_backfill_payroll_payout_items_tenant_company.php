<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::connection('legacy_new')->hasTable('payroll_payout_items')
            || !Schema::connection('legacy_new')->hasTable('payroll_payouts')
        ) {
            return;
        }

        if (!Schema::connection('legacy_new')->hasColumn('payroll_payout_items', 'tenant_id')
            || !Schema::connection('legacy_new')->hasColumn('payroll_payout_items', 'company_id')
        ) {
            return;
        }

        DB::connection('legacy_new')->statement('
            UPDATE payroll_payout_items i
            JOIN payroll_payouts p ON p.id = i.payout_id
            SET i.tenant_id = p.tenant_id,
                i.company_id = p.company_id
            WHERE i.tenant_id IS NULL OR i.company_id IS NULL
        ');
    }

    public function down(): void
    {
        // no-op
    }
};
