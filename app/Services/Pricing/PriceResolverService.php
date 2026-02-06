<?php

declare(strict_types=1);

namespace App\Services\Pricing;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductCompanyPrice;
use App\Services\Pricing\DTO\PriceDTO;
use RuntimeException;

final class PriceResolverService
{
    public function getPrices(int $tenantId, int $companyId, int $productId): PriceDTO
    {
        $mode = (string) config('pricing.mode', 'dual_read');

        $priceRow = ProductCompanyPrice::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('product_id', $productId)
            ->first();

        if ($priceRow) {
            return new PriceDTO(
                price: $priceRow->price !== null ? (float) $priceRow->price : null,
                price_sale: $priceRow->price_sale !== null ? (float) $priceRow->price_sale : null,
                price_delivery: $priceRow->price_delivery !== null ? (float) $priceRow->price_delivery : null,
                montaj: $priceRow->montaj !== null ? (float) $priceRow->montaj : null,
                montaj_sebest: $priceRow->montaj_sebest !== null ? (float) $priceRow->montaj_sebest : null,
                currency: $priceRow->currency ?? 'RUB',
                is_active: (bool) ($priceRow->is_active ?? true),
                is_fallback: false,
            );
        }

        if ($mode === 'company_table_only') {
            throw new RuntimeException("Price not found for product_id={$productId} (tenant_id={$tenantId}, company_id={$companyId}).");
        }

        $product = Product::query()
            ->select(['id', 'price', 'price_sale', 'price_delivery', 'montaj', 'montaj_sebest'])
            ->where('id', $productId)
            ->first();

        if (!$product) {
            throw new RuntimeException("Product not found for price fallback (product_id={$productId}).");
        }

        return new PriceDTO(
            price: $product->price !== null ? (float) $product->price : null,
            price_sale: $product->price_sale !== null ? (float) $product->price_sale : null,
            price_delivery: $product->price_delivery !== null ? (float) $product->price_delivery : null,
            montaj: $product->montaj !== null ? (float) $product->montaj : null,
            montaj_sebest: $product->montaj_sebest !== null ? (float) $product->montaj_sebest : null,
            currency: 'RUB',
            is_active: true,
            is_fallback: true,
        );
    }
}
