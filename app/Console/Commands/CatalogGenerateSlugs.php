<?php

namespace App\Console\Commands;

use App\Services\Catalog\CatalogSlugService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CatalogGenerateSlugs extends Command
{
    protected $signature = 'catalog:generate-slugs
        {--connection=legacy_new : Database connection}
        {--tenant= : Only for a specific tenant_id}
        {--company= : Only for a specific company_id}
        {--chunk=200 : Chunk size}';

    protected $description = 'Generate missing slugs for product_categories, product_subcategories and product_brands (unique per tenant_id + company_id).';

    public function handle(CatalogSlugService $slugService): int
    {
        $connection = trim((string) ($this->option('connection') ?? 'legacy_new'));
        $chunk = (int) ($this->option('chunk') ?? 200);
        $chunk = $chunk > 0 ? $chunk : 200;

        $tenantId = $this->option('tenant');
        $tenantId = is_string($tenantId) && $tenantId !== '' ? (int) $tenantId : null;

        $companyId = $this->option('company');
        $companyId = is_string($companyId) && $companyId !== '' ? (int) $companyId : null;

        $schema = Schema::connection($connection);
        $tables = [
            'product_categories',
            'product_subcategories',
            'product_brands',
        ];

        foreach ($tables as $table) {
            if (!$schema->hasTable($table) || !$schema->hasColumn($table, 'slug')) {
                $this->warn("Skip {$table}: table or slug column not found.");
                continue;
            }

            $this->info("Generating slugs for {$table}...");
            $updated = $this->backfillTable($slugService, $connection, $table, $tenantId, $companyId, $chunk);
            $this->info("Updated rows in {$table}: {$updated}");
        }

        return self::SUCCESS;
    }

    private function backfillTable(
        CatalogSlugService $slugService,
        string $connection,
        string $table,
        ?int $tenantId,
        ?int $companyId,
        int $chunk,
    ): int {
        $query = DB::connection($connection)
            ->table($table)
            ->select(['id', 'tenant_id', 'company_id', 'name', 'slug'])
            ->where(function ($q) {
                $q->whereNull('slug')->orWhere('slug', '');
            });

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }
        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            return 0;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;
        $query->orderBy('id')->chunkById($chunk, function ($rows) use (
            $slugService,
            $connection,
            $table,
            &$updated,
            $bar,
        ): void {
            foreach ($rows as $row) {
                $tenantId = $row->tenant_id === null ? null : (int) $row->tenant_id;
                $companyId = $row->company_id === null ? null : (int) $row->company_id;
                $name = (string) ($row->name ?? '');

                $slug = $slugService->uniqueForTable($connection, $table, $name, $tenantId, $companyId, (int) $row->id);

                DB::connection($connection)
                    ->table($table)
                    ->where('id', $row->id)
                    ->update(['slug' => $slug]);

                $updated++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        return $updated;
    }
}

