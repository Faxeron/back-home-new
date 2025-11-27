<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->create('cashbox_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cashbox_id');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->decimal('balance_after', 14, 2);
            $table->timestamps();

            $table->foreign('cashbox_id')->references('id')->on('cash_boxes')->cascadeOnDelete();
            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
            $table->index(['cashbox_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->dropIfExists('cashbox_history');
    }
};
