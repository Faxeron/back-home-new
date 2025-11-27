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
            if (Schema::connection('legacy_new')->hasColumn('transactions', 'id_spending_item')) {
                $table->dropColumn('id_spending_item');
            }
            if (Schema::connection('legacy_new')->hasColumn('transactions', 'id_counterparty')) {
                $table->dropColumn('id_counterparty');
            }
            if (Schema::connection('legacy_new')->hasColumn('transactions', 'id_deal')) {
                $table->dropColumn('id_deal');
            }

            if (Schema::connection('legacy_new')->hasColumn('transactions', 'created_by_user_id')) {
                $table->renameColumn('created_by_user_id', 'created_by');
            }
            if (Schema::connection('legacy_new')->hasColumn('transactions', 'updated_by_user_id')) {
                $table->renameColumn('updated_by_user_id', 'updated_by');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->table('transactions', function (Blueprint $table): void {
            if (!Schema::connection('legacy_new')->hasColumn('transactions', 'id_spending_item')) {
                $table->unsignedBigInteger('id_spending_item')->nullable();
            }
            if (!Schema::connection('legacy_new')->hasColumn('transactions', 'id_counterparty')) {
                $table->unsignedBigInteger('id_counterparty')->nullable();
            }
            if (!Schema::connection('legacy_new')->hasColumn('transactions', 'id_deal')) {
                $table->unsignedBigInteger('id_deal')->nullable();
            }
            if (Schema::connection('legacy_new')->hasColumn('transactions', 'created_by')) {
                $table->renameColumn('created_by', 'created_by_user_id');
            }
            if (Schema::connection('legacy_new')->hasColumn('transactions', 'updated_by')) {
                $table->renameColumn('updated_by', 'updated_by_user_id');
            }
        });
    }
};
