<?php

namespace App\Modules\PublicApi\Transformers;

use App\Domain\Catalog\Models\Product;
use App\Modules\PublicApi\DTO\ProductPageDTO;

final class ProductPageTransformer
{
    public function toDTO(Product $product, int $companyId): ProductPageDTO
    {
        $description = null;
        if ($product->relationLoaded('description') && $product->description) {
            $description = $product->description->description_long
                ?: $product->description->description_short;
        }

        $images = [];
        if ($product->relationLoaded('media')) {
            $images = $product->media
                ->map(fn ($media) => (string) $media->url)
                ->filter(fn ($url) => $url !== '')
                ->values()
                ->all();
        }

        $specs = [];
        if ($product->relationLoaded('attributeValues')) {
            $specs = $product->attributeValues->map(function ($item) {
                $value = $item->value_number !== null ? $item->value_number : $item->value_string;

                return [
                    'name' => $item->definition?->name,
                    'value' => $value,
                ];
            })->filter(fn ($row) => !empty($row['name']))->values()->all();
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

        $hasCompanyPrice = $product->pcp_company_id !== null;
        $price = $hasCompanyPrice ? $product->pcp_price : null;
        $priceSale = $hasCompanyPrice ? ($product->pcp_price_sale ?? $product->pcp_price) : null;
        $priceDelivery = $hasCompanyPrice ? $product->pcp_price_delivery : null;
        $montaj = $hasCompanyPrice ? $product->pcp_montaj : null;
        $currency = $product->pcp_currency ?: 'RUB';

        return new ProductPageDTO(
            id: (int) $product->id,
            slug: (string) $product->slug,
            name: (string) $product->name,
            description: $description,
            specs: $specs,
            images: $images,
            price: $price !== null ? (float) $price : null,
            price_sale: $priceSale !== null ? (float) $priceSale : null,
            price_delivery: $priceDelivery !== null ? (float) $priceDelivery : null,
            montaj: $montaj !== null ? (float) $montaj : null,
            currency: $currency !== null ? (string) $currency : null,
            brand: $brand,
            category: $category,
            faq: [],
            seo: null,
            company_id: $companyId,
        );
    }
}
