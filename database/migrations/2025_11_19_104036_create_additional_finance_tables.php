<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('legacy_new');

        $schema->create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(100);
        });

        DB::connection('legacy_new')->table('payment_methods')->insert([
            ['code' => 'CASH', 'name' => 'Cash', 'sort_order' => 10],
            ['code' => 'BANK_TRANSFER', 'name' => 'Bank transfer', 'sort_order' => 20],
            ['code' => 'ONLINE_PAYMENT', 'name' => 'Online payment', 'sort_order' => 30],
            ['code' => 'CARD', 'name' => 'Card (POS)', 'sort_order' => 40],
            ['code' => 'CASH_IN', 'name' => 'Cash in', 'sort_order' => 50],
            ['code' => 'PERSONAL_CARD', 'name' => 'Personal card', 'sort_order' => 60],
        ]);

        $schema->create('transaction_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->tinyInteger('sign')->default(1);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(100);
        });

        DB::connection('legacy_new')->table('transaction_types')->insert([
            ['code' => 'INCOME', 'name' => 'Приход', 'sign' => 1, 'sort_order' => 10],
            ['code' => 'EXPENSE', 'name' => 'Расход', 'sign' => -1, 'sort_order' => 20],
            ['code' => 'TRANSFER_IN', 'name' => 'Перевод в кассу', 'sign' => 1, 'sort_order' => 30],
            ['code' => 'TRANSFER_OUT', 'name' => 'Перевод из кассы', 'sign' => -1, 'sort_order' => 40],
            ['code' => 'ADVANCE', 'name' => 'Аванс/подотчет', 'sign' => -1, 'sort_order' => 50],
            ['code' => 'REFUND', 'name' => 'Возврат в кассу', 'sign' => 1, 'sort_order' => 60],
        ]);

        $schema->create('spendings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('cash_box_id');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('spending_item_id');
            $table->unsignedBigInteger('fond_id');
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->decimal('summ', 14, 2);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('cash_box_id')->references('id')->on('cash_boxes');
            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
            $table->foreign('spending_item_id')->references('id')->on('spending_items');
            $table->foreign('fond_id')->references('id')->on('spending_funds');
            $table->foreign('contract_id')->references('id')->on('contracts')->nullOnDelete();
        });

        $schema->create('receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('cash_box_id');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->decimal('summ', 14, 2);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('cash_box_id')->references('id')->on('cash_boxes');
            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
            $table->foreign('contract_id')->references('id')->on('contracts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('legacy_new');

        $schema->dropIfExists('receipts');
        $schema->dropIfExists('spendings');
        $schema->dropIfExists('transaction_types');
        $schema->dropIfExists('payment_methods');
    }
};
