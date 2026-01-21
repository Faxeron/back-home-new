<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->create('payroll_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id');
            $table->decimal('manager_fixed', 12, 2)->default(1000);
            $table->decimal('manager_percent', 5, 2)->default(7);
            $table->decimal('measurer_fixed', 12, 2)->default(1000);
            $table->decimal('measurer_percent', 5, 2)->default(5);
            $table->timestamps();

            $table->unique(['tenant_id', 'company_id'], 'payroll_settings_tenant_company_unique');
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->dropIfExists('payroll_settings');
    }
};
