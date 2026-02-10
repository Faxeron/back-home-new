<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'legacy_new';

    public function up(): void
    {
        Schema::connection($this->connection)->create('report_cashflow_monthly_summary', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->index();
            $table->char('year_month', 7)->unique(); // YYYY-MM unique with company
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('inflow_total', 15, 2)->default(0);
            $table->decimal('outflow_total', 15, 2)->default(0);
            $table->decimal('net_cashflow', 15, 2)->default(0);
            $table->decimal('closing_balance', 15, 2)->default(0);
            $table->timestamp('updated_at')->nullable();

            $table->unique(['tenant_id', 'company_id', 'year_month'], 'ix_cf_summary_unique');
            $table->index(['tenant_id', 'company_id', 'year_month'], 'ix_cf_summary_month');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('report_cashflow_monthly_summary');
    }
};
