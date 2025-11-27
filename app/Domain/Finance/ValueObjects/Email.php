<?php

namespace App\Domain\Finance\ValueObjects;

final class Email
{
    public function __construct(private readonly ?string $value)
    {
    }

    public static function from(?string $value): self
    {
        return new self($value ? strtolower(trim($value)) : null);
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
