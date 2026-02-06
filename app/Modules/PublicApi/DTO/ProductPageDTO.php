<?php

namespace App\Modules\PublicApi\DTO;

final class ProductPageDTO
{
    /**
     * @param array<int, array<string, mixed>> $specs
     * @param array<int, string> $images
     * @param array<string, mixed>|null $brand
     * @param array<string, mixed>|null $category
     * @param array<int, array<string, mixed>> $faq
     * @param array<string, mixed>|null $seo
     */
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public ?string $description,
        public array $specs,
        public array $images,
        public ?float $price,
        public ?float $price_sale,
        public ?float $price_delivery,
        public ?float $montaj,
        public ?string $currency,
        public ?array $brand,
        public ?array $category,
        public array $faq,
        public ?array $seo,
        public ?int $company_id,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'specs' => $this->specs,
            'images' => $this->images,
            'price' => $this->price,
            'price_sale' => $this->price_sale,
            'price_delivery' => $this->price_delivery,
            'montaj' => $this->montaj,
            'currency' => $this->currency,
            'brand' => $this->brand,
            'category' => $this->category,
            'faq' => $this->faq,
            'seo' => $this->seo,
            'company_id' => $this->company_id,
        ];
    }
}
