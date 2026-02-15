<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'legacy_new';

    public function up(): void
    {
        $this->moveAfterName('cities');
        $this->moveAfterName('products');
    }

    public function down(): void
    {
        // No-op: column order change is not critical to roll back.
    }

    private function moveAfterName(string $table): void
    {
        $driver = DB::connection($this->connection)->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        $schema = Schema::connection($this->connection);
        if (!$schema->hasTable($table)) {
            return;
        }
        if (!$schema->hasColumn($table, 'name') || !$schema->hasColumn($table, 'slug')) {
            return;
        }

        DB::connection($this->connection)->statement(
            "ALTER TABLE `{$table}` MODIFY COLUMN `slug` varchar(191) NULL AFTER `name`"
        );
    }
};
