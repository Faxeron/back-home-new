<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GenerateSlugs extends Command
{
    protected $signature = 'slugs:generate
        {--table= : Table name}
        {--field= : Source field}
        {--slug-field=slug : Slug column}
        {--tenant-column= : Tenant column (default: tenant_id if exists)}
        {--connection=legacy_new : Database connection}
        {--force : Regenerate even if slug is already set}
        {--chunk=200 : Chunk size}';

    protected $description = 'Generate slugs for a table with transliteration and per-tenant uniqueness';

    public function handle(): int
    {
        $table = trim((string) $this->option('table'));
        $field = trim((string) $this->option('field'));
        $slugField = trim((string) ($this->option('slug-field') ?? 'slug'));
        $connection = trim((string) ($this->option('connection') ?? 'legacy_new'));
        $tenantColumn = $this->option('tenant-column');
        $force = (bool) $this->option('force');
        $chunk = (int) ($this->option('chunk') ?? 200);
        $chunk = $chunk > 0 ? $chunk : 200;

        if ($table === '' || $field === '') {
            $this->error('Options --table and --field are required.');
            return self::FAILURE;
        }

        $schema = Schema::connection($connection);
        if (!$schema->hasTable($table)) {
            $this->error("Table '{$table}' not found on connection '{$connection}'.");
            return self::FAILURE;
        }
        if (!$schema->hasColumn($table, $field)) {
            $this->error("Field '{$field}' not found in table '{$table}'.");
            return self::FAILURE;
        }
        if (!$schema->hasColumn($table, $slugField)) {
            $this->error("Slug field '{$slugField}' not found in table '{$table}'.");
            return self::FAILURE;
        }

        $tenantColumn = is_string($tenantColumn) ? trim($tenantColumn) : null;
        if ($tenantColumn === '') {
            $tenantColumn = null;
        }
        if ($tenantColumn !== null && !$schema->hasColumn($table, $tenantColumn)) {
            $this->error("Tenant column '{$tenantColumn}' not found in table '{$table}'.");
            return self::FAILURE;
        }
        if ($tenantColumn === null && $schema->hasColumn($table, 'tenant_id')) {
            $tenantColumn = 'tenant_id';
        }
        if ($tenantColumn === null) {
            $this->warn('Tenant column not found. Uniqueness will be enforced globally.');
        }

        $existing = [];
        if (!$force) {
            $this->info('Loading existing slugs...');
            $existingQuery = DB::connection($connection)
                ->table($table)
                ->select(['id', $slugField]);

            if ($tenantColumn) {
                $existingQuery->addSelect($tenantColumn);
            }

            $existingQuery
                ->whereNotNull($slugField)
                ->where($slugField, '!=', '')
                ->orderBy('id')
                ->chunkById($chunk, function ($rows) use (&$existing, $slugField, $tenantColumn): void {
                    foreach ($rows as $row) {
                        $tenantKey = $tenantColumn ? (string) ($row->{$tenantColumn} ?? '') : '_global';
                        $slug = (string) ($row->{$slugField} ?? '');
                        if ($slug === '') {
                            continue;
                        }
                        $existing[$tenantKey][$slug] = true;
                    }
                });
        }

        $query = DB::connection($connection)
            ->table($table)
            ->select(['id', $field, $slugField]);

        if ($tenantColumn) {
            $query->addSelect($tenantColumn);
        }

        if (!$force) {
            $query->where(function ($q) use ($slugField) {
                $q->whereNull($slugField)->orWhere($slugField, '');
            });
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('No rows to update.');
            return self::SUCCESS;
        }

        $this->info("Generating slugs for {$total} rows...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;
        $query->orderBy('id')->chunkById($chunk, function ($rows) use (
            &$existing,
            $field,
            $slugField,
            $tenantColumn,
            $table,
            $connection,
            &$updated,
            $bar
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

                DB::connection($connection)
                    ->table($table)
                    ->where('id', $row->id)
                    ->update([$slugField => $slug]);

                $existing[$tenantKey][$slug] = true;
                $updated++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Updated rows: {$updated}");

        return self::SUCCESS;
    }

    private function makeSlug(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return Str::slug($value, '-', 'ru');
    }
}
