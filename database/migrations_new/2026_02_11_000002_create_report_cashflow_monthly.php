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
        Schema::connection($this->connection)->create('report_cashflow_monthly', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->char('year_month', 7)->index(); // YYYY-MM
            $table->enum('section', ['OPERATING', 'INVESTING', 'FINANCING']);
            $table->enum('direction', ['IN', 'OUT']);
            $table->unsignedBigInteger('cashflow_item_id');
            $table->string('cashflow_item_name', 255);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->integer('tx_count')->default(0);
            $table->timestamp('updated_at')->nullable();

            $table->unique(['tenant_id', 'company_id', 'year_month', 'cashflow_item_id'], 'ix_cf_monthly_unique');
            $table->index(['tenant_id', 'company_id', 'year_month'], 'ix_cf_monthly_month');

            $table->foreign('cashflow_item_id')
                ->references('id')
                ->on('cashflow_items')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('report_cashflow_monthly');
    }
};
