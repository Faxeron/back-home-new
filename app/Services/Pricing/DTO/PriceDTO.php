<?php

declare(strict_types=1);

namespace App\Services\Pricing\DTO;

final class PriceDTO
{
    public function __construct(
        public ?float $price,
        public ?float $price_sale,
        public ?float $price_delivery,
        public ?float $montaj,
        public ?float $montaj_sebest,
        public string $currency = 'RUB',
        public bool $is_active = true,
        public bool $is_fallback = false,
    ) {
    }

    public static function empty(): self
    {
        return new self(null, null, null, null, null, 'RUB', true, false);
    }

    public function toArray(): array
    {
        return [
            'price' => $this->price,
            'price_sale' => $this->price_sale,
            'price_delivery' => $this->price_delivery,
            'montaj' => $this->montaj,
            'montaj_sebest' => $this->montaj_sebest,
            'currency' => $this->currency,
            'is_active' => $this->is_active,
            'is_fallback' => $this->is_fallback,
        ];
    }
}
