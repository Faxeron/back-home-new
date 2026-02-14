<?php

declare(strict_types=1);

namespace App\Domain\Finance\Enums;

enum FinanceObjectStatus: string
{
    case DRAFT = 'DRAFT';
    case ACTIVE = 'ACTIVE';
    case ON_HOLD = 'ON_HOLD';
    case DONE = 'DONE';
    case CANCELED = 'CANCELED';
    case ARCHIVED = 'ARCHIVED';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status) => $status->value, self::cases());
    }
}

