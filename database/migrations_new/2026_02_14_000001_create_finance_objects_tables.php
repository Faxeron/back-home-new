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
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('finance_objects')) {
            $schema->create('finance_objects', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->enum('type', [
                    'CONTRACT',
                    'PROJECT',
                    'EVENT',
                    'ORDER',
                    'SUBSCRIPTION',
                    'TENDER',
                    'SERVICE',
                    'INTERNAL',
                    'LEGACY_IMPORT',
                ]);
                $table->string('name', 255);
                $table->string('code', 120)->nullable();
                $table->enum('status', ['DRAFT', 'ACTIVE', 'ON_HOLD', 'DONE', 'CANCELED', 'ARCHIVED'])->default('DRAFT');
                $table->date('date_from');
                $table->date('date_to')->nullable();
                $table->unsignedBigInteger('counterparty_id')->nullable();
                $table->unsignedBigInteger('legal_contract_id')->nullable();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'company_id'], 'finance_objects_tenant_company_idx');
                $table->index('type', 'finance_objects_type_idx');
                $table->index('status', 'finance_objects_status_idx');
                $table->unique(['tenant_id', 'company_id', 'code'], 'finance_objects_code_company_unique');

                $table->foreign('counterparty_id', 'finance_objects_counterparty_fk')
                    ->references('id')
                    ->on('counterparties')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
                $table->foreign('legal_contract_id', 'finance_objects_legal_contract_fk')
                    ->references('id')
                    ->on('contracts')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
                $table->foreign('created_by', 'finance_objects_created_by_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
                $table->foreign('updated_by', 'finance_objects_updated_by_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }

        if (!$schema->hasTable('finance_object_allocations')) {
            $schema->create('finance_object_allocations', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('transaction_id');
                $table->unsignedBigInteger('finance_object_id');
                $table->decimal('amount', 15, 2);
                $table->text('comment')->nullable();
                $table->timestamps();

                $table->unique(['transaction_id', 'finance_object_id'], 'finance_object_allocations_tx_object_unique');
                $table->index('finance_object_id', 'finance_object_allocations_object_idx');
                $table->index('transaction_id', 'finance_object_allocations_tx_idx');
                $table->index(['tenant_id', 'company_id'], 'finance_object_allocations_tenant_company_idx');

                $table->foreign('transaction_id', 'finance_object_allocations_tx_fk')
                    ->references('id')
                    ->on('transactions')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
                $table->foreign('finance_object_id', 'finance_object_allocations_object_fk')
                    ->references('id')
                    ->on('finance_objects')
                    ->restrictOnDelete()
                    ->cascadeOnUpdate();
            });
        }
    }

    public function down(): void
    {
        // forward-only migration
    }
};

