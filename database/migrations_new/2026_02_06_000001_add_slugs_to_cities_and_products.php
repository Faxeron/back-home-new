<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    protected $connection = 'legacy_new';

    public function up(): void
    {
        $this->addSlugColumn('cities');
        $this->addSlugColumn('products');

        $this->fillSlugs('cities', 'name', 'slug', null);
        $this->fillSlugs('products', 'name', 'slug', 'tenant_id');
    }

    public function down(): void
    {
        $this->dropSlugColumn('cities');
        $this->dropSlugColumn('products');
    }

    private function addSlugColumn(string $table): void
    {
        if (!Schema::connection($this->connection)->hasTable($table)) {
            return;
        }

        if (!Schema::connection($this->connection)->hasColumn($table, 'slug')) {
            Schema::connection($this->connection)->table($table, function (Blueprint $table): void {
                $table->string('slug', 191)->nullable()->index();
            });
        }
    }

    private function dropSlugColumn(string $table): void
    {
        if (!Schema::connection($this->connection)->hasTable($table)) {
            return;
        }

        if (Schema::connection($this->connection)->hasColumn($table, 'slug')) {
            if ($this->indexExists($table, "{$table}_slug_index")) {
                $this->dropIndex($table, "{$table}_slug_index");
            }
            Schema::connection($this->connection)->table($table, function (Blueprint $table): void {
                $table->dropColumn('slug');
            });
        }
    }

    private function fillSlugs(string $table, string $field, string $slugField, ?string $tenantColumn): void
    {
        $schema = Schema::connection($this->connection);
        if (!$schema->hasTable($table) || !$schema->hasColumn($table, $field) || !$schema->hasColumn($table, $slugField)) {
            return;
        }

        if ($tenantColumn !== null && !$schema->hasColumn($table, $tenantColumn)) {
            $tenantColumn = null;
        }
        if ($tenantColumn === null && $schema->hasColumn($table, 'tenant_id')) {
            $tenantColumn = 'tenant_id';
        }

        $existing = [];
        $existingQuery = DB::connection($this->connection)
            ->table($table)
            ->select(['id', $slugField]);

        if ($tenantColumn) {
            $existingQuery->addSelect($tenantColumn);
        }

        $existingQuery
            ->whereNotNull($slugField)
            ->where($slugField, '!=', '')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$existing, $slugField, $tenantColumn): void {
                foreach ($rows as $row) {
                    $tenantKey = $tenantColumn ? (string) ($row->{$tenantColumn} ?? '') : '_global';
                    $slug = (string) ($row->{$slugField} ?? '');
                    if ($slug === '') {
                        continue;
                    }
                    $existing[$tenantKey][$slug] = true;
                }
            });

        $query = DB::connection($this->connection)
            ->table($table)
            ->select(['id', $field, $slugField]);

        if ($tenantColumn) {
            $query->addSelect($tenantColumn);
        }

        $query
            ->where(function ($q) use ($slugField) {
                $q->whereNull($slugField)->orWhere($slugField, '');
            })
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (
                &$existing,
                $table,
                $field,
                $slugField,
                $tenantColumn
            ): void {
                foreach ($rows as $row) {
                    $source = (string) ($row->{$field} ?? '');
                    $base = $this->makeSlug($source);
                    if ($base === '') {
                        $base = $row->id ? 'item-' . $row->id : 'item';
                    }

                    $tenantKey = $tenantColumn ? (string) ($row->{$tenantColumn} ?? '') : '_global';
                    if (!isset($existing[$tenantKey])) {
                        $existing[$tenantKey] = [];
                    }

                    $slug = $base;
                    $suffix = 2;
                    while (isset($existing[$tenantKey][$slug])) {
                        $slug = $base . '-' . $suffix;
                        $suffix++;
                    }

                    DB::connection($this->connection)
                        ->table($table)
                        ->where('id', $row->id)
                        ->update([$slugField => $slug]);

                    $existing[$tenantKey][$slug] = true;
                }
            });
    }

    private function makeSlug(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return Str::slug($value, '-', 'ru');
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();
        $db = $connection->getDatabaseName();

        if ($driver === 'pgsql') {
            return $connection->table('pg_indexes')
                ->where('schemaname', 'public')
                ->where('tablename', $table)
                ->where('indexname', $index)
                ->exists();
        }

        return $connection->table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $db)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();
    }

    private function dropIndex(string $table, string $index): void
    {
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $connection->statement("ALTER TABLE {$table} DROP INDEX {$index}");

            return;
        }

        $connection->statement("DROP INDEX IF EXISTS {$index}");
    }
};
