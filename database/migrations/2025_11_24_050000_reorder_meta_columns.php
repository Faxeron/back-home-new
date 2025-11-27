<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tables to skip.
     *
     * @var array<int, string>
     */
    private array $skip = [
        'migrations',
    ];

    public function up(): void
    {
        $conn = DB::connection('legacy_new');
        $dbName = $conn->getDatabaseName();

        $tables = collect($conn->select('SHOW TABLES'))->map(function ($row) {
            $arr = get_object_vars($row);
            return reset($arr);
        });

        foreach ($tables as $table) {
            if (in_array($table, $this->skip, true)) {
                continue;
            }

            if (!$this->hasColumn($conn, $dbName, $table, 'id')) {
                continue;
            }

            // Ensure required columns exist
            $this->ensureColumn($conn, $table, 'tenant_id', 'BIGINT UNSIGNED NULL', 'id');
            $this->ensureColumn($conn, $table, 'company_id', 'BIGINT UNSIGNED NULL', 'tenant_id');
            $this->ensureColumn($conn, $table, 'created_at', 'TIMESTAMP NULL DEFAULT NULL');
            $this->ensureColumn($conn, $table, 'created_by', 'BIGINT UNSIGNED NULL');
            $this->ensureColumn($conn, $table, 'updated_at', 'TIMESTAMP NULL DEFAULT NULL');
            $this->ensureColumn($conn, $table, 'updated_by', 'BIGINT UNSIGNED NULL');

            // Reorder meta columns
            $otherColumns = $this->otherColumns($conn, $dbName, $table);
            $lastOther = $otherColumns ? end($otherColumns) : 'company_id';

            $conn->statement("ALTER TABLE `{$table}` MODIFY `tenant_id` BIGINT UNSIGNED NULL AFTER `id`");
            $conn->statement("ALTER TABLE `{$table}` MODIFY `company_id` BIGINT UNSIGNED NULL AFTER `tenant_id`");

            $afterCreatedAt = $lastOther ?: 'company_id';
            $conn->statement("ALTER TABLE `{$table}` MODIFY `created_at` TIMESTAMP NULL DEFAULT NULL AFTER `{$afterCreatedAt}`");
            $conn->statement("ALTER TABLE `{$table}` MODIFY `created_by` BIGINT UNSIGNED NULL AFTER `created_at`");
            $conn->statement("ALTER TABLE `{$table}` MODIFY `updated_at` TIMESTAMP NULL DEFAULT NULL AFTER `created_by`");
            $conn->statement("ALTER TABLE `{$table}` MODIFY `updated_by` BIGINT UNSIGNED NULL AFTER `updated_at`");
        }
    }

    public function down(): void
    {
        // No column drops; order changes are non-critical to roll back.
    }

    /**
     * Check column existence.
     */
    private function hasColumn($conn, string $db, string $table, string $column): bool
    {
        return $conn->table('information_schema.columns')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->exists();
    }

    /**
     * Ensure column exists; if missing, add at optional position.
     */
    private function ensureColumn($conn, string $table, string $column, string $definition, string $after = null): void
    {
        $db = $conn->getDatabaseName();
        if ($this->hasColumn($conn, $db, $table, $column)) {
            return;
        }

        $afterSql = $after ? " AFTER `{$after}`" : '';
        $conn->statement("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}{$afterSql}");
    }

    /**
     * Get list of columns excluding meta ones.
     *
     * @return array<int, string>
     */
    private function otherColumns($conn, string $db, string $table): array
    {
        $meta = ['id', 'tenant_id', 'company_id', 'created_at', 'created_by', 'updated_at', 'updated_by'];

        return $conn->table('information_schema.columns')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->orderBy('ordinal_position')
            ->pluck('column_name')
            ->filter(function ($col) use ($meta) {
                return !in_array($col, $meta, true);
            })
            ->values()
            ->toArray();
    }
};
