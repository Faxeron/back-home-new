<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->table('transactions', function (Blueprint $table): void {
            $table->unsignedBigInteger('transaction_type_id')->after('id_cash_box');
            $table->unsignedBigInteger('payment_method_id')->after('transaction_type_id');

            $table->foreign('transaction_type_id')->references('id')->on('transaction_types')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->table('transactions', function (Blueprint $table): void {
            $table->dropForeign(['transaction_type_id']);
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn(['transaction_type_id', 'payment_method_id']);
        });
    }
};
