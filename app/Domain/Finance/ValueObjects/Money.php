<?php

namespace App\Domain\Finance\ValueObjects;

use JsonSerializable;

final class Money implements JsonSerializable
{
    public function __construct(
        private readonly string $amount,
        private readonly string $currency = 'RUB'
    ) {
    }

    public static function fromNumeric(int|float|string|null $value, string $currency = 'RUB'): self
    {
        $normalized = number_format((float) ($value ?? 0), 2, '.', '');

        return new self($normalized, strtoupper($currency));
    }

    public function toFloat(): float
    {
        return (float) $this->amount;
    }

    public function amount(): string
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function __toString(): string
    {
        return $this->amount;
    }

    public function jsonSerialize(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }
}
