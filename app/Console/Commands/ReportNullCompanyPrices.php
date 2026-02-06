<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Catalog\Models\Product;
use Illuminate\Console\Command;

final class ReportNullCompanyPrices extends Command
{
    protected $signature = 'pricing:report-null-company-prices {--tenant=1} {--companies=1,2} {--limit=20}';

    protected $description = 'Report active products where company price row is missing/inactive or has NULL price fields.';

    public function handle(): int
    {
        $tenantId = (int) $this->option('tenant');
        $companiesRaw = (string) $this->option('companies');
        $limit = (int) $this->option('limit');
        $limit = $limit < 0 ? 0 : $limit;

        $companies = array_values(array_filter(array_map(
            static fn ($value) => (int) trim((string) $value),
            explode(',', $companiesRaw)
        )));

        if ($tenantId <= 0) {
            $this->error('Invalid tenant id.');
            return self::FAILURE;
        }

        if ($companies === []) {
            $this->error('No companies specified. Use --companies=1,2');
            return self::FAILURE;
        }

        $hasProblems = false;

        foreach ($companies as $companyId) {
            if ($companyId <= 0) {
                continue;
            }

            $baseQuery = Product::query()
                ->where('products.tenant_id', $tenantId)
                ->whereNull('products.archived_at')
                ->where('products.is_visible', true)
                ->where(function ($builder) use ($companyId): void {
                    $builder->where('products.company_id', $companyId)
                        ->orWhere('products.is_global', true);
                });

            $total = (clone $baseQuery)->count();

            $activeJoin = (clone $baseQuery)->leftJoin('product_company_prices as pcp', function ($join) use ($tenantId, $companyId): void {
                $join->on('pcp.product_id', '=', 'products.id')
                    ->where('pcp.tenant_id', '=', $tenantId)
                    ->where('pcp.company_id', '=', $companyId)
                    ->where('pcp.is_active', '=', 1);
            });

            $missingActiveRowCount = (clone $activeJoin)->whereNull('pcp.id')->count();

            $nullPriceFieldsCount = (clone $activeJoin)
                ->whereNotNull('pcp.id')
                ->whereNull('pcp.price')
                ->whereNull('pcp.price_sale')
                ->count();

            // Extra signal: how many rows exist but are inactive (will look like "missing" for public API).
            $inactiveRowCount = (clone $baseQuery)->join('product_company_prices as pcp', function ($join) use ($tenantId, $companyId): void {
                $join->on('pcp.product_id', '=', 'products.id')
                    ->where('pcp.tenant_id', '=', $tenantId)
                    ->where('pcp.company_id', '=', $companyId)
                    ->where('pcp.is_active', '=', 0);
            })->count('pcp.id');

            $this->newLine();
            $this->info("Company {$companyId}:");
            $this->line("- total products in public catalog scope: {$total}");
            $this->line("- missing active price rows (product_company_prices is_active=1): {$missingActiveRowCount}");
            $this->line("- price rows exist but both price and price_sale are NULL: {$nullPriceFieldsCount}");
            $this->line("- inactive price rows (is_active=0): {$inactiveRowCount}");

            if ($missingActiveRowCount > 0 || $nullPriceFieldsCount > 0 || $inactiveRowCount > 0) {
                $hasProblems = true;
            }

            if ($limit <= 0) {
                continue;
            }

            if ($missingActiveRowCount > 0) {
                $rows = (clone $activeJoin)
                    ->whereNull('pcp.id')
                    ->orderBy('products.id')
                    ->limit($limit)
                    ->get(['products.id', 'products.scu', 'products.slug', 'products.name']);

                if ($rows->isNotEmpty()) {
                    $this->warn('Examples (missing active price row):');
                    $this->table(['ID', 'SCU', 'Slug', 'Name'], $rows->map(static fn ($row) => [
                        $row->id,
                        $row->scu,
                        $row->slug,
                        $row->name,
                    ])->all());
                }
            }

            if ($nullPriceFieldsCount > 0) {
                $rows = (clone $activeJoin)
                    ->whereNotNull('pcp.id')
                    ->whereNull('pcp.price')
                    ->whereNull('pcp.price_sale')
                    ->orderBy('products.id')
                    ->limit($limit)
                    ->get(['products.id', 'products.scu', 'products.slug', 'products.name', 'pcp.currency', 'pcp.is_active']);

                if ($rows->isNotEmpty()) {
                    $this->warn('Examples (price row exists but price fields are NULL):');
                    $this->table(['ID', 'SCU', 'Slug', 'Name', 'Currency', 'Active'], $rows->map(static fn ($row) => [
                        $row->id,
                        $row->scu,
                        $row->slug,
                        $row->name,
                        $row->currency,
                        $row->is_active ? 1 : 0,
                    ])->all());
                }
            }
        }

        return $hasProblems ? self::FAILURE : self::SUCCESS;
    }
}

