<?php

namespace App\Domain\Finance\ValueObjects;

final class Phone
{
    public function __construct(private readonly ?string $value)
    {
    }

    public static function from(?string $value): self
    {
        return new self($value ? trim($value) : null);
    }

    public function value(): ?string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value ?? '';
    }
}
