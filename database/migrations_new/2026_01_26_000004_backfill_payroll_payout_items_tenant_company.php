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

        $connection = DB::connection('legacy_new');
        if ($connection->getDriverName() === 'pgsql') {
            $connection->statement('
                UPDATE payroll_payout_items i
                SET tenant_id = p.tenant_id,
                    company_id = p.company_id
                FROM payroll_payouts p
                WHERE p.id = i.payout_id
                  AND (i.tenant_id IS NULL OR i.company_id IS NULL)
            ');

            return;
        }

        $connection->statement('
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
