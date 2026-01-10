<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->create('finance_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 100);
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['tenant_id', 'company_id'], 'finance_audit_logs_tenant_company_idx');
            $table->index('action', 'finance_audit_logs_action_idx');
            $table->index('created_at', 'finance_audit_logs_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->dropIfExists('finance_audit_logs');
    }
};
