<?php

namespace App\Modules\PublicApi\DTO;

final class ProductPageDTO
{
    /**
     * @param array<string, mixed>|null $category
     * @param array<string, mixed>|null $subcategory
     * @param array<string, mixed>|null $brand
     * @param array<string, mixed> $price
     * @param array<int, array<string, mixed>> $media
     * @param array<int, array<string, mixed>> $attributes
     * @param array<int, array<string, mixed>> $related_products
     * @param array<string, mixed>|null $seo
     */
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public int $sort_order,
        public bool $is_top,
        public bool $is_new,
        public ?array $category,
        public ?array $subcategory,
        public ?array $brand,
        public array $price,
        public ?string $description_short,
        public ?string $description_long,
        public array $media,
        public array $attributes,
        public array $related_products,
        public ?array $seo,
        public int $company_id,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'sort_order' => $this->sort_order,
            'is_top' => $this->is_top,
            'is_new' => $this->is_new,
            'category' => $this->category,
            'subcategory' => $this->subcategory,
            'brand' => $this->brand,
            'price' => $this->price,
            'description_short' => $this->description_short,
            'description_long' => $this->description_long,
            'media' => $this->media,
            'attributes' => $this->attributes,
            'related_products' => $this->related_products,
            'seo' => $this->seo,
            'company_id' => $this->company_id,
        ];
    }
}

