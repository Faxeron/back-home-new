<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'legacy_new';

    public function up(): void
    {
        $path = database_path('schema/legacy_new-schema.sql');
        if (!file_exists($path)) {
            throw new RuntimeException('Schema dump not found: ' . $path);
        }

        $sql = file_get_contents($path);
        DB::connection($this->connection)->unprepared($sql);
    }

    public function down(): void
    {
        // Drop all tables on legacy_new (including migrations); intended only for local/dev refresh.
        Schema::connection($this->connection)->dropAllTables();
    }
};
