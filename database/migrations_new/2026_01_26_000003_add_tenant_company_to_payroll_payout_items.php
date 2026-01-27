<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->table('payroll_payout_items', function (Blueprint $table): void {
            if (!Schema::connection('legacy_new')->hasColumn('payroll_payout_items', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            }
            if (!Schema::connection('legacy_new')->hasColumn('payroll_payout_items', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('tenant_id');
            }
        });

        Schema::connection('legacy_new')->table('payroll_payout_items', function (Blueprint $table): void {
            if (Schema::connection('legacy_new')->hasColumn('payroll_payout_items', 'tenant_id')
                && Schema::connection('legacy_new')->hasColumn('payroll_payout_items', 'company_id')
            ) {
                $table->index(['tenant_id', 'company_id'], 'payroll_payout_items_tenant_company_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->table('payroll_payout_items', function (Blueprint $table): void {
            if (Schema::connection('legacy_new')->hasIndex('payroll_payout_items', 'payroll_payout_items_tenant_company_idx')) {
                $table->dropIndex('payroll_payout_items_tenant_company_idx');
            }
            if (Schema::connection('legacy_new')->hasColumn('payroll_payout_items', 'company_id')) {
                $table->dropColumn('company_id');
            }
            if (Schema::connection('legacy_new')->hasColumn('payroll_payout_items', 'tenant_id')) {
                $table->dropColumn('tenant_id');
            }
        });
    }
};
