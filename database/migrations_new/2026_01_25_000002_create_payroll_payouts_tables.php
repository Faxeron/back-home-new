<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->create('payroll_payouts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('cashbox_id');
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->unsignedBigInteger('fund_id');
            $table->unsignedBigInteger('spending_item_id')->nullable();
            $table->date('payout_date');
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->text('comment')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'user_id'], 'payroll_payouts_company_user_idx');
            $table->index(['company_id', 'payout_date'], 'payroll_payouts_company_date_idx');
        });

        Schema::connection('legacy_new')->create('payroll_payout_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payout_id');
            $table->unsignedBigInteger('accrual_id');
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->unsignedBigInteger('contract_document_id')->nullable();
            $table->unsignedBigInteger('spending_id')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->timestamps();

            $table->index(['payout_id'], 'payroll_payout_items_payout_idx');
            $table->index(['accrual_id'], 'payroll_payout_items_accrual_idx');
            $table->index(['contract_id'], 'payroll_payout_items_contract_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->dropIfExists('payroll_payout_items');
        Schema::connection('legacy_new')->dropIfExists('payroll_payouts');
    }
};
