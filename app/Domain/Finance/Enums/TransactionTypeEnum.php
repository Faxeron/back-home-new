<?php

namespace App\Domain\Finance\Enums;

enum TransactionTypeEnum: string
{
    case INCOME = 'INCOME';
    case EXPENSE = 'EXPENSE';
    case TRANSFER_IN = 'TRANSFER_IN';
    case TRANSFER_OUT = 'TRANSFER_OUT';
    case ADVANCE = 'ADVANCE';
    case REFUND = 'REFUND';

    public static function fromCode(?string $code): self
    {
        return self::tryFrom(strtoupper((string) $code)) ?? self::INCOME;
    }

    public function code(): string
    {
        return $this->value;
    }

    public function sign(): int
    {
        return match ($this) {
            self::INCOME,
            self::TRANSFER_IN,
            self::REFUND => +1,

            self::EXPENSE,
            self::TRANSFER_OUT,
            self::ADVANCE => -1,
        };
    }
}
