<?php

namespace App\Modules\PublicApi\Transformers;

use App\Domain\Catalog\Models\Product;
use App\Modules\PublicApi\DTO\ProductCardDTO;

final class ProductCardTransformer
{
    public function toDTO(Product $product, int $companyId): ProductCardDTO
    {
        $hasCompanyPrice = $product->pcp_company_id !== null;
        $price = $hasCompanyPrice ? ($product->pcp_price !== null ? (float) $product->pcp_price : null) : null;
        $priceSale = $hasCompanyPrice ? ($product->pcp_price_sale !== null ? (float) $product->pcp_price_sale : $price) : null;
        $priceDelivery = $hasCompanyPrice ? ($product->pcp_price_delivery !== null ? (float) $product->pcp_price_delivery : null) : null;
        $montaj = $hasCompanyPrice ? ($product->pcp_montaj !== null ? (float) $product->pcp_montaj : null) : null;
        $currency = $hasCompanyPrice ? (($product->pcp_currency ?: 'RUB') ?: 'RUB') : 'RUB';

        $image = null;
        if ($product->relationLoaded('media')) {
            $main = $product->media->firstWhere('is_main', true) ?? $product->media->first();
            if ($main) {
                $url = trim((string) ($main->url ?? ''));
                $image = $url !== '' ? $url : null;
            }
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

        $descriptionShort = null;
        if ($product->relationLoaded('description') && $product->description) {
            $descriptionShortRaw = (string) ($product->description->description_short ?? '');
            $descriptionShort = trim($descriptionShortRaw) !== '' ? trim($descriptionShortRaw) : null;
        }

        return new ProductCardDTO(
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
            image: $image,
            description_short: $descriptionShort,
            company_id: (int) $companyId,
        );
    }
}
