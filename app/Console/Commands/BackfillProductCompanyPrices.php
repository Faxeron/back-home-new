<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductCompanyPrice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class BackfillProductCompanyPrices extends Command
{
    protected $signature = 'pricing:backfill-company-prices {--tenant=1} {--companies=1,2} {--dry-run}';

    protected $description = 'Ensure product_company_prices rows exist for active products (prices may remain NULL).';

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

        $productModel = new Product();
        $schema = Schema::connection($productModel->getConnectionName());
        $hasLegacyPriceCols = $schema->hasColumn('products', 'price')
            && $schema->hasColumn('products', 'price_sale')
            && $schema->hasColumn('products', 'price_delivery')
            && $schema->hasColumn('products', 'montaj')
            && $schema->hasColumn('products', 'montaj_sebest');

        if ($hasLegacyPriceCols) {
            $this->warn('Detected legacy operational price columns on products; will copy into product_company_prices for missing rows.');
        }

        foreach ($companies as $companyId) {
            $this->newLine();
            $this->info("Processing company_id={$companyId}...");

            $created = 0;
            $skipped = 0;

            Product::query()
                ->select(array_values(array_filter([
                    'id',
                    'tenant_id',
                    'company_id',
                    $hasLegacyPriceCols ? 'price' : null,
                    $hasLegacyPriceCols ? 'price_sale' : null,
                    $hasLegacyPriceCols ? 'price_delivery' : null,
                    $hasLegacyPriceCols ? 'montaj' : null,
                    $hasLegacyPriceCols ? 'montaj_sebest' : null,
                ])))
                ->where('tenant_id', $tenantId)
                ->whereNull('archived_at')
                ->where('is_visible', true)
                ->where(function ($builder) use ($companyId): void {
                    $builder->where('company_id', $companyId)
                        ->orWhere('is_global', true);
                })
                ->orderBy('id')
                ->chunkById(500, function ($products) use ($tenantId, $companyId, $dryRun, &$created, &$skipped): void {
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
                            $skipped++;
                            continue;
                        } else {
                            $created++;
                        }

                        // If legacy columns exist (before cutover), preserve old prices into the new table.
                        $legacy = [];
                        foreach (['price', 'price_sale', 'price_delivery', 'montaj', 'montaj_sebest'] as $field) {
                            if (isset($product->{$field})) {
                                $legacy[$field] = $product->{$field};
                            }
                        }

                        $rows[] = [
                            'tenant_id' => $tenantId,
                            'company_id' => $companyId,
                            'product_id' => $productId,
                            'price' => $legacy['price'] ?? null,
                            'price_sale' => $legacy['price_sale'] ?? null,
                            'price_delivery' => $legacy['price_delivery'] ?? null,
                            'montaj' => $legacy['montaj'] ?? null,
                            'montaj_sebest' => $legacy['montaj_sebest'] ?? null,
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
                            'currency',
                            'is_active',
                            'updated_at',
                        ]
                    );
                });

            $this->info("Company {$companyId}: created={$created}, skipped={$skipped}");
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
