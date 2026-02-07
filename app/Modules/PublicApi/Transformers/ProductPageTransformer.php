<?php

namespace App\Modules\PublicApi\Transformers;

use App\Domain\Catalog\Models\Product;
use App\Modules\PublicApi\DTO\ProductCardDTO;
use App\Modules\PublicApi\DTO\ProductPageDTO;

final class ProductPageTransformer
{
    /**
     * @param array<int, ProductCardDTO> $related
     */
    public function toDTO(Product $product, int $companyId, array $related = []): ProductPageDTO
    {
        $descriptionShort = null;
        $descriptionLong = null;
        if ($product->relationLoaded('description') && $product->description) {
            $short = trim((string) ($product->description->description_short ?? ''));
            $long = trim((string) ($product->description->description_long ?? ''));
            $descriptionShort = $short !== '' ? $short : null;
            $descriptionLong = $long !== '' ? $long : null;
        }

        $media = [];
        if ($product->relationLoaded('media')) {
            $media = $product->media
                ->map(fn ($m) => [
                    'url' => (string) ($m->url ?? ''),
                    'alt' => $m->alt ? (string) $m->alt : null,
                    'is_main' => (bool) ($m->is_main ?? false),
                    'sort_order' => (int) ($m->sort_order ?? 0),
                    'type' => $m->type ? (string) $m->type : null,
                ])
                ->filter(fn ($row) => trim((string) ($row['url'] ?? '')) !== '')
                ->values()
                ->all();
        }

        $attributes = [];
        if ($product->relationLoaded('attributeValues')) {
            $attributes = $product->attributeValues->map(function ($item) {
                $value = $item->value_number !== null ? $item->value_number : $item->value_string;

                return [
                    'id' => $item->definition?->id ? (int) $item->definition->id : null,
                    'code' => $item->definition?->code ? (string) $item->definition->code : null,
                    'name' => $item->definition?->name ? (string) $item->definition->name : null,
                    'value' => $value,
                    'unit' => $item->definition?->unit ? (string) $item->definition->unit : null,
                ];
            })->filter(fn ($row) => !empty($row['name']))->values()->all();
        }

        $category = null;
        if ($product->relationLoaded('category') && $product->category) {
            $category = [
                'id' => (int) $product->category->id,
                'slug' => (string) $product->category->slug,
                'name' => (string) $product->category->name,
            ];
        }

        $subcategory = null;
        if ($product->relationLoaded('subCategory') && $product->subCategory) {
            $subcategory = [
                'id' => (int) $product->subCategory->id,
                'slug' => (string) $product->subCategory->slug,
                'name' => (string) $product->subCategory->name,
            ];
        }

        $brand = null;
        if ($product->relationLoaded('brand') && $product->brand) {
            $brand = [
                'id' => (int) $product->brand->id,
                'slug' => (string) $product->brand->slug,
                'name' => (string) $product->brand->name,
            ];
        }

        $hasCompanyPrice = $product->pcp_company_id !== null;
        $price = $hasCompanyPrice ? ($product->pcp_price !== null ? (float) $product->pcp_price : null) : null;
        $priceSale = $hasCompanyPrice ? ($product->pcp_price_sale !== null ? (float) $product->pcp_price_sale : $price) : null;
        $priceDelivery = $hasCompanyPrice ? ($product->pcp_price_delivery !== null ? (float) $product->pcp_price_delivery : null) : null;
        $montaj = $hasCompanyPrice ? ($product->pcp_montaj !== null ? (float) $product->pcp_montaj : null) : null;
        $currency = $hasCompanyPrice ? (($product->pcp_currency ?: 'RUB') ?: 'RUB') : 'RUB';

        return new ProductPageDTO(
            id: (int) $product->id,
            slug: (string) $product->slug,
            name: (string) $product->name,
            sort_order: (int) ($product->sort_order ?? 0),
            is_top: (bool) ($product->is_top ?? false),
            is_new: (bool) ($product->is_new ?? false),
            category: $category,
            subcategory: $subcategory,
            brand: $brand,
            price: [
                'price' => $price,
                'price_sale' => $priceSale,
                'price_delivery' => $priceDelivery,
                'montaj' => $montaj,
                'currency' => $currency,
            ],
            description_short: $descriptionShort,
            description_long: $descriptionLong,
            media: $media,
            attributes: $attributes,
            related_products: array_values(array_map(fn (ProductCardDTO $dto) => $dto->toArray(), $related)),
            seo: null,
            company_id: (int) $companyId,
        );
    }
}
