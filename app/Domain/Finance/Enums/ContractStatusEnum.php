<?php

namespace App\Domain\Finance\Enums;

enum ContractStatusEnum: string
{
    case UNKNOWN = 'UNKNOWN';
    case ACTIVE = 'ACTIVE';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';
    case ON_HOLD = 'ON_HOLD';

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
