<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Catalog\Models\Product;
use Illuminate\Console\Command;

class ReportMissingCompanyPrices extends Command
{
    protected $signature = 'pricing:report-missing-company-prices {--tenant=1} {--companies=1,2} {--limit=20}';

    protected $description = 'Report active products without company prices in product_company_prices.';

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

        $hasMissing = false;

        foreach ($companies as $companyId) {
            if ($companyId <= 0) {
                continue;
            }

            $baseQuery = Product::query()
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->whereNull('archived_at');

            $total = (clone $baseQuery)->count();

            $missingQuery = (clone $baseQuery)->leftJoin('product_company_prices as pcp', function ($join) use ($tenantId, $companyId) {
                $join->on('pcp.product_id', '=', 'products.id')
                    ->where('pcp.tenant_id', '=', $tenantId)
                    ->where('pcp.company_id', '=', $companyId);
            });

            $missingCount = (clone $missingQuery)->whereNull('pcp.id')->count();

            $this->info("Company {$companyId}: {$missingCount} missing of {$total} active products.");

            if ($missingCount > 0) {
                $hasMissing = true;

                if ($limit > 0) {
                    $rows = (clone $missingQuery)
                        ->whereNull('pcp.id')
                        ->orderBy('products.id')
                        ->limit($limit)
                        ->get(['products.id', 'products.scu', 'products.name']);

                    if ($rows->isNotEmpty()) {
                        $this->table(['ID', 'SCU', 'Name'], $rows->map(static fn ($row) => [
                            $row->id,
                            $row->scu,
                            $row->name,
                        ])->all());
                    }
                }
            }
        }

        return $hasMissing ? self::FAILURE : self::SUCCESS;
    }
}
