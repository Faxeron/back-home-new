<?php

namespace App\Modules\PublicApi\DTO;

final class ProductCardDTO
{
    /**
     * @param array<int, string> $images
     * @param array<string, mixed>|null $category
     * @param array<string, mixed>|null $brand
     * @param array<int, string> $city_available
     */
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public ?float $price,
        public ?float $price_sale,
        public ?float $price_delivery,
        public ?float $montaj,
        public ?string $currency,
        public ?float $old_price,
        public array $images,
        public ?array $category,
        public ?array $brand,
        public array $city_available,
        public ?int $company_id,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'price' => $this->price,
            'price_sale' => $this->price_sale,
            'price_delivery' => $this->price_delivery,
            'montaj' => $this->montaj,
            'currency' => $this->currency,
            'old_price' => $this->old_price,
            'images' => $this->images,
            'category' => $this->category,
            'brand' => $this->brand,
            'city_available' => $this->city_available,
            'company_id' => $this->company_id,
        ];
    }
}
