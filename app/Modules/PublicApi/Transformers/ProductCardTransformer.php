<?php

namespace App\Modules\PublicApi\Transformers;

use App\Domain\Catalog\Models\Product;
use App\Modules\PublicApi\DTO\ProductCardDTO;

final class ProductCardTransformer
{
    /**
     * @param array<int, array<int, string>> $cityMap
     */
    public function toDTO(Product $product, int $companyId, array $cityMap = []): ProductCardDTO
    {
        $hasCompanyPrice = $product->pcp_company_id !== null;
        $price = $hasCompanyPrice ? $product->pcp_price : null;
        $priceSale = $hasCompanyPrice ? ($product->pcp_price_sale ?? $product->pcp_price) : null;
        $priceDelivery = $hasCompanyPrice ? $product->pcp_price_delivery : null;
        $montaj = $hasCompanyPrice ? $product->pcp_montaj : null;
        $currency = $product->pcp_currency ?: 'RUB';

        $images = [];
        if ($product->relationLoaded('media')) {
            $images = $product->media
                ->map(fn ($media) => (string) $media->url)
                ->filter(fn ($url) => $url !== '')
                ->values()
                ->all();
        }

        $category = null;
        if ($product->relationLoaded('category') && $product->category) {
            $category = [
                'id' => $product->category->id,
                'name' => $product->category->name,
            ];
        }

        $brand = null;
        if ($product->relationLoaded('brand') && $product->brand) {
            $brand = [
                'id' => $product->brand->id,
                'name' => $product->brand->name,
            ];
        }

        $cityAvailable = isset($cityMap[$companyId]) ? $cityMap[$companyId] : [];

        return new ProductCardDTO(
            id: (int) $product->id,
            slug: (string) $product->slug,
            name: (string) $product->name,
            price: $price !== null ? (float) $price : null,
            price_sale: $priceSale !== null ? (float) $priceSale : null,
            price_delivery: $priceDelivery !== null ? (float) $priceDelivery : null,
            montaj: $montaj !== null ? (float) $montaj : null,
            currency: $currency !== null ? (string) $currency : null,
            images: $images,
            category: $category,
            brand: $brand,
            city_available: $cityAvailable,
            company_id: $companyId,
        );
    }
}
