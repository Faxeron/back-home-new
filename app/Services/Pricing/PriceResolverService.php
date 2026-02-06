<?php

declare(strict_types=1);

namespace App\Services\Pricing;

use App\Domain\Catalog\Models\ProductCompanyPrice;
use App\Services\Pricing\DTO\PriceDTO;
use RuntimeException;

final class PriceResolverService
{
    public function tryGetPrices(int $tenantId, int $companyId, int $productId): ?PriceDTO
    {
        $priceRow = ProductCompanyPrice::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('product_id', $productId)
            ->first();

        if (!$priceRow) {
            return null;
        }

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

    public function getPrices(int $tenantId, int $companyId, int $productId): PriceDTO
    {
        $dto = $this->tryGetPrices($tenantId, $companyId, $productId);
        if (!$dto) {
            throw new RuntimeException("Price not found for product_id={$productId} (tenant_id={$tenantId}, company_id={$companyId}).");
        }

        return $dto;
    }
}
