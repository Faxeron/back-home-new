<?php

namespace App\Modules\PublicApi\DTO;

final class ProductCardDTO
{
    /**
     * @param array<string, mixed>|null $category
     * @param array<string, mixed>|null $subcategory
     * @param array<string, mixed>|null $brand
     * @param array<string, mixed> $price
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
        public ?string $image,
        public ?string $description_short,
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
            'image' => $this->image,
            'description_short' => $this->description_short,
            'company_id' => $this->company_id,
        ];
    }
}

