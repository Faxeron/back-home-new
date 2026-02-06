<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductCompanyPrice;
use Illuminate\Console\Command;

class BackfillProductCompanyPrices extends Command
{
    protected $signature = 'pricing:backfill-company-prices {--tenant=1} {--companies=1,2} {--dry-run}';

    protected $description = 'Backfill product_company_prices from products (price fields) for selected tenant/companies.';

    public function handle(): int
    {
        $tenantId = (int) $this->option('tenant');
        $companies = $this->parseCompanies((string) $this->option('companies'));
        $dryRun = (bool) $this->option('dry-run');

        if ($tenantId <= 0) {
            $this->error('Invalid tenant id.');
            return self::FAILURE;
        }

        if (empty($companies)) {
            $this->error('No companies provided. Use --companies=1,2');
            return self::FAILURE;
        }

        $this->info('Pricing backfill started.');
        $this->info('Tenant: ' . $tenantId);
        $this->info('Companies: ' . implode(', ', $companies));
        if ($dryRun) {
            $this->warn('Dry-run mode: no writes will be performed.');
        }

        foreach ($companies as $companyId) {
            $this->newLine();
            $this->info("Processing company_id={$companyId}...");

            $created = 0;
            $updated = 0;

            Product::query()
                ->select([
                    'id',
                    'tenant_id',
                    'company_id',
                    'price',
                    'price_sale',
                    'price_delivery',
                    'montaj',
                    'montaj_sebest',
                ])
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->orderBy('id')
                ->chunkById(500, function ($products) use ($tenantId, $companyId, $dryRun, &$created, &$updated): void {
                    if ($products->isEmpty()) {
                        return;
                    }

                    $productIds = $products->pluck('id')->all();
                    $existing = ProductCompanyPrice::query()
                        ->where('tenant_id', $tenantId)
                        ->where('company_id', $companyId)
                        ->whereIn('product_id', $productIds)
                        ->pluck('product_id')
                        ->all();

                    $existingMap = array_fill_keys($existing, true);
                    $rows = [];
                    $now = now();

                    foreach ($products as $product) {
                        $productId = (int) $product->id;
                        if (isset($existingMap[$productId])) {
                            $updated++;
                        } else {
                            $created++;
                        }

                        $rows[] = [
                            'tenant_id' => $tenantId,
                            'company_id' => $companyId,
                            'product_id' => $productId,
                            'price' => $product->price,
                            'price_sale' => $product->price_sale,
                            'price_delivery' => $product->price_delivery,
                            'montaj' => $product->montaj,
                            'montaj_sebest' => $product->montaj_sebest,
                            'currency' => 'RUB',
                            'is_active' => 1,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if ($dryRun || empty($rows)) {
                        return;
                    }

                    ProductCompanyPrice::query()->upsert(
                        $rows,
                        ['tenant_id', 'company_id', 'product_id'],
                        [
                            'price',
                            'price_sale',
                            'price_delivery',
                            'montaj',
                            'montaj_sebest',
                            'currency',
                            'is_active',
                            'updated_at',
                        ]
                    );
                });

            $this->info("Company {$companyId}: created={$created}, updated={$updated}");
        }

        $this->info('Pricing backfill finished.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, int>
     */
    private function parseCompanies(string $value): array
    {
        $parts = array_filter(array_map('trim', explode(',', $value)));
        $ids = array_map('intval', $parts);
        $ids = array_values(array_filter($ids, fn ($id) => $id > 0));

        return array_values(array_unique($ids));
    }
}
