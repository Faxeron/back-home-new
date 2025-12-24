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
        Schema::connection('legacy_new')->create('transactions', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_paid')->default(false);
            $table->date('date_is_paid')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->date('date_is_completed')->nullable();
            $table->decimal('sum', 15, 2)->default(0);
            $table->unsignedBigInteger('id_spending_item')->nullable();
            $table->unsignedBigInteger('id_cash_box')->nullable();
            $table->unsignedBigInteger('id_counterparty')->nullable();
            $table->unsignedBigInteger('id_project')->nullable();
            $table->unsignedBigInteger('id_deal')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('legacy_new')->dropIfExists('transactions');
    }
};
