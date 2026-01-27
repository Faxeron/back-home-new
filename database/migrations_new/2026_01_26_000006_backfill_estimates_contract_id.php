<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::connection('legacy_new')->hasTable('estimates')) {
            return;
        }

        if (!Schema::connection('legacy_new')->hasColumn('estimates', 'contract_id')) {
            return;
        }

        DB::connection('legacy_new')->statement('
            UPDATE estimates
            SET contract_id = client_id
            WHERE contract_id IS NULL AND client_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        // no-op
    }
};
