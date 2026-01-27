<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->table('estimates', function (Blueprint $table): void {
            if (!Schema::connection('legacy_new')->hasColumn('estimates', 'contract_id')) {
                $table->unsignedBigInteger('contract_id')->nullable()->after('client_id');
            }
        });

        Schema::connection('legacy_new')->table('estimates', function (Blueprint $table): void {
            if (Schema::connection('legacy_new')->hasColumn('estimates', 'contract_id')) {
                $table->index(['contract_id'], 'estimates_contract_id_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->table('estimates', function (Blueprint $table): void {
            if (Schema::connection('legacy_new')->hasIndex('estimates', 'estimates_contract_id_idx')) {
                $table->dropIndex('estimates_contract_id_idx');
            }
            if (Schema::connection('legacy_new')->hasColumn('estimates', 'contract_id')) {
                $table->dropColumn('contract_id');
            }
        });
    }
};
