<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $schema = Schema::connection('legacy_new');

        $schema->create('spending_funds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        $schema->create('spending_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('fond_id');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('fond_id')->references('id')->on('spending_funds')->cascadeOnDelete();
        });

        $schema->create('cash_boxes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('balance', 14, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        $schema->create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });

        $schema->create('cash_box_company', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cash_box_id');
            $table->unsignedBigInteger('company_id');
            $table->timestamps();

            $table->foreign('cash_box_id')->references('id')->on('cash_boxes')->cascadeOnDelete();
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['cash_box_id', 'company_id']);
        });

        $schema->create('counterparties', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('name');
            $table->string('phone', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('address')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        $schema->create('counterparty_individuals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('counterparty_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('patronymic')->nullable();
            $table->string('passport_series', 50);
            $table->string('passport_number', 50);
            $table->string('issued_by')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('snils', 50)->nullable();
            $table->string('inn', 50)->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('counterparty_id')->references('id')->on('counterparties')->cascadeOnDelete();
        });

        $schema->create('counterparty_companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('counterparty_id');
            $table->string('legal_name');
            $table->string('short_name')->nullable();
            $table->string('inn', 50);
            $table->string('kpp', 50)->nullable();
            $table->string('ogrn', 50);
            $table->text('legal_address');
            $table->text('postal_address')->nullable();
            $table->string('director_name')->nullable();
            $table->string('accountant_name')->nullable();
            $table->string('bank_name');
            $table->string('bik', 50);
            $table->string('account_number', 64);
            $table->string('correspondent_account', 64);
            $table->timestamps();

            $table->foreign('counterparty_id')->references('id')->on('counterparties')->cascadeOnDelete();
        });

        $schema->create('contract_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->string('color', 20)->nullable();
            $table->integer('sort_order')->default(100);
            $table->boolean('is_active')->default(true);
        });

        $schema->create('contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('counterparty_id');
            $table->unsignedBigInteger('id_contract_status');
            $table->string('system_status_code', 50)->default('NOT_PAID');
            $table->string('title');
            $table->decimal('total_amount', 14, 2)->nullable();
            $table->decimal('paid_amount', 14, 2)->nullable();
            $table->boolean('is_completed')->default(false);
            $table->date('contract_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->text('comment')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('counterparty_id')->references('id')->on('counterparties');
            $table->foreign('id_contract_status')->references('id')->on('contract_statuses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $schema = Schema::connection('legacy_new');

        $schema->dropIfExists('contracts');
        $schema->dropIfExists('contract_statuses');
        $schema->dropIfExists('counterparty_companies');
        $schema->dropIfExists('counterparty_individuals');
        $schema->dropIfExists('counterparties');
        $schema->dropIfExists('cash_box_company');
        $schema->dropIfExists('companies');
        $schema->dropIfExists('cash_boxes');
        $schema->dropIfExists('spending_items');
        $schema->dropIfExists('spending_funds');
    }
};
