<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection('legacy_new')->hasTable('districts')) {
            Schema::connection('legacy_new')->rename('districts', 'cities_districts');
        }
    }

    public function down(): void
    {
        if (Schema::connection('legacy_new')->hasTable('cities_districts')) {
            Schema::connection('legacy_new')->rename('cities_districts', 'districts');
        }
    }
};
