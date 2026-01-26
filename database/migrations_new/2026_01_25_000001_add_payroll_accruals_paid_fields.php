<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->table('payroll_accruals', function (Blueprint $table): void {
            if (!Schema::connection('legacy_new')->hasColumn('payroll_accruals', 'paid_amount')) {
                $table->decimal('paid_amount', 14, 2)->default(0)->after('amount');
            }
            if (!Schema::connection('legacy_new')->hasColumn('payroll_accruals', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('paid_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->table('payroll_accruals', function (Blueprint $table): void {
            if (Schema::connection('legacy_new')->hasColumn('payroll_accruals', 'paid_at')) {
                $table->dropColumn('paid_at');
            }
            if (Schema::connection('legacy_new')->hasColumn('payroll_accruals', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }
        });
    }
};
