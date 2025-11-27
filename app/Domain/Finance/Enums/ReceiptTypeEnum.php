<?php

namespace App\Domain\Finance\Enums;

enum ReceiptTypeEnum: string
{
    case UNKNOWN = 'UNKNOWN';
    case INCOME = 'INCOME';
    case REFUND = 'REFUND';

    public static function fromCode(?string $code): self
    {
        if ($code === null) {
            return self::UNKNOWN;
        }

        return self::tryFrom(strtoupper($code)) ?? self::UNKNOWN;
    }

    public function code(): string
    {
        return $this->value;
    }
}
