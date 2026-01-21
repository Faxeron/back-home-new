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
            'work_kind' => $this->work_kind,
            'product_kind_id' => $this->product_kind_id,
            'spending_type_id' => $this->spending_type_id,
            'scu' => $this->scu,
            'sort_order' => $this->sort_order,
            'category_id' => $this->category_id,
            'sub_category_id' => $this->sub_category_id,
            'brand_id' => $this->brand_id,
            'unit_id' => $this->unit_id,
            'price' => $this->price,
            'price_sale' => $this->price_sale,
            'price_vendor' => $this->price_vendor,
            'price_vendor_min' => $this->price_vendor_min,
            'price_zakup' => $this->price_zakup,
            'price_delivery' => $this->price_delivery,
            'montaj' => $this->montaj,
            'montaj_sebest' => $this->montaj_sebest,
            'is_global' => $this->is_global,
            'is_visible' => $this->is_visible,
            'is_top' => $this->is_top,
            'is_new' => $this->is_new,
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
            'kind' => $this->whenLoaded('kind', fn () => [
                'id' => $this->kind?->id,
                'name' => $this->kind?->name,
            ]),
            'description' => $this->whenLoaded('description', fn () => [
                'description_short' => $this->description?->description_short,
                'description_long' => $this->description?->description_long,
                'dignities' => $this->description?->dignities,
                'constructive' => $this->description?->constructive,
                'avito1' => $this->description?->avito1,
                'avito2' => $this->description?->avito2,
            ]),
            'media' => $this->whenLoaded('media', fn () => $this->media->map(fn ($item) => [
                'id' => $item->id,
                'type' => $item->type,
                'url' => $item->url,
                'sort_order' => $item->sort_order,
            ])),
            'attributes' => $this->whenLoaded('attributeValues', fn () => $this->attributeValues->map(fn ($item) => [
                'id' => $item->id,
                'attribute_id' => $item->attribute_id,
                'name' => $item->definition?->name,
                'value_string' => $item->value_string,
                'value_number' => $item->value_number,
            ])),
            'relations' => $this->whenLoaded('relations', fn () => $this->relations->map(fn ($item) => [
                'id' => $item->id,
                'relation_type' => $item->relation_type,
                'related_product' => $item->relatedProduct ? [
                    'id' => $item->relatedProduct?->id,
                    'name' => $item->relatedProduct?->name,
                    'scu' => $item->relatedProduct?->scu,
                ] : null,
            ])),
        ];
    }
}
