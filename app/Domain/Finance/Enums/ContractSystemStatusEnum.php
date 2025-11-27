<?php

namespace App\Domain\Finance\Enums;

enum ContractSystemStatusEnum: string
{
    case NOT_PAID = 'NOT_PAID';
    case PARTIALLY_PAID = 'PARTIALLY_PAID';
    case FULLY_PAID = 'FULLY_PAID';
    case OVERPAID = 'OVERPAID';

    public function code(): string
    {
        return $this->value;
    }
}
