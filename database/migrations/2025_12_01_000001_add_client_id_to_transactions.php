<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->table('transactions', function (Blueprint $table) {
            if (!Schema::connection('legacy_new')->hasColumn('transactions', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable()->after('counterparty_id');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->table('transactions', function (Blueprint $table) {
            if (Schema::connection('legacy_new')->hasColumn('transactions', 'client_id')) {
                $table->dropColumn('client_id');
            }
        });
    }
};
