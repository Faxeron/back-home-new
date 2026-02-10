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
        Schema::connection($this->connection)->create('report_debts_daily', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->index();
            $table->date('snapshot_date')->index();
            $table->enum('type', ['AR', 'AP']); // AR=debitor, AP=creditor
            $table->unsignedBigInteger('entity_id'); // contract_id for AR, counterparty_id/doc_id for AP
            $table->string('entity_title', 255);
            $table->decimal('amount_total', 15, 2);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('amount_debt', 15, 2)->default(0);
            $table->integer('days_overdue')->default(0);
            $table->json('meta_json')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['tenant_id', 'company_id', 'snapshot_date', 'type', 'entity_id'], 'ix_debt_unique');
            $table->index(['tenant_id', 'company_id', 'snapshot_date', 'type'], 'ix_debt_snapshot');
            $table->index(['tenant_id', 'company_id', 'type', 'amount_debt'], 'ix_debt_type');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('report_debts_daily');
    }
};
