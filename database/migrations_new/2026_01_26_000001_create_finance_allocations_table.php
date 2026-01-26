<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->create('finance_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('spending_id')->nullable();
            $table->unsignedBigInteger('receipt_id')->nullable();
            $table->unsignedBigInteger('contract_id');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('kind', 32)->default('expense');
            $table->text('comment')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['contract_id'], 'finance_alloc_contract_idx');
            $table->index(['spending_id'], 'finance_alloc_spending_idx');
            $table->index(['receipt_id'], 'finance_alloc_receipt_idx');
            $table->index(['kind'], 'finance_alloc_kind_idx');
            $table->index(['company_id'], 'finance_alloc_company_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->dropIfExists('finance_allocations');
    }
};
