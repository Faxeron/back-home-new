<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->create('payroll_accruals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('contract_document_id')->nullable();
            $table->unsignedBigInteger('rule_id')->nullable();
            $table->string('document_type', 20)->nullable();
            $table->string('type', 30);
            $table->string('source', 20)->default('system');
            $table->string('status', 20)->default('active');
            $table->decimal('base_amount', 14, 2)->default(0);
            $table->decimal('percent', 6, 2)->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->text('comment')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'user_id'], 'payroll_accruals_contract_user_idx');
            $table->index(['contract_document_id'], 'payroll_accruals_doc_idx');
            $table->index(['company_id', 'status'], 'payroll_accruals_company_status_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->dropIfExists('payroll_accruals');
    }
};
