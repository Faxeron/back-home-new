<?php

declare(strict_types=1);

namespace App\Domain\Finance\Enums;

enum FinanceObjectType: string
{
    case CONTRACT = 'CONTRACT';
    case PROJECT = 'PROJECT';
    case EVENT = 'EVENT';
    case ORDER = 'ORDER';
    case SUBSCRIPTION = 'SUBSCRIPTION';
    case TENDER = 'TENDER';
    case SERVICE = 'SERVICE';
    case INTERNAL = 'INTERNAL';
    case LEGACY_IMPORT = 'LEGACY_IMPORT';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type) => $type->value, self::cases());
    }
}

