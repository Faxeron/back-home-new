<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Catalog\Models\Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'product_type_id' => $this->product_type_id,
            'spending_type_id' => $this->spending_type_id,
            'scu' => $this->scu,
            'category_id' => $this->category_id,
            'sub_category_id' => $this->sub_category_id,
            'brand_id' => $this->brand_id,
            'price' => $this->price,
            'price_sale' => $this->price_sale,
            'price_vendor' => $this->price_vendor,
            'price_zakup' => $this->price_zakup,
            'delivery_price' => $this->delivery_price,
            'montaj' => $this->montaj,
            'montaj_sebest' => $this->montaj_sebest,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
            ]),
            'sub_category' => $this->whenLoaded('subCategory', fn () => [
                'id' => $this->subCategory?->id,
                'name' => $this->subCategory?->name,
            ]),
            'brand' => $this->whenLoaded('brand', fn () => [
                'id' => $this->brand?->id,
                'name' => $this->brand?->name,
            ]),
        ];
    }
}
