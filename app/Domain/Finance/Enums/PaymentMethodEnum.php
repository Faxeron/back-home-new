<?php

namespace App\Domain\Finance\Enums;

enum PaymentMethodEnum: string
{
    case CASH = 'CASH';
    case BANK_TRANSFER = 'BANK_TRANSFER';
    case ONLINE_PAYMENT = 'ONLINE_PAYMENT';
    case CARD = 'CARD';
    case CASH_IN = 'CASH_IN';
    case PERSONAL_CARD = 'PERSONAL_CARD';

    public static function fromCode(?string $code): self
    {
        return self::tryFrom(strtoupper((string) $code)) ?? self::CASH;
    }

    public function code(): string
    {
        return $this->value;
    }
}
