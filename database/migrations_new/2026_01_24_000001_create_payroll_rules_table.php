<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->create('payroll_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->string('document_type', 20)->default('combined');
            $table->decimal('fixed_amount', 12, 2)->default(0);
            $table->decimal('margin_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'company_id', 'user_id', 'document_type'],
                'payroll_rules_tenant_company_user_doc_unique'
            );
            $table->index(['company_id', 'user_id'], 'payroll_rules_company_user_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->dropIfExists('payroll_rules');
    }
};
