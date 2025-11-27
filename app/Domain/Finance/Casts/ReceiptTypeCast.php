<?php

namespace App\Domain\Finance\Casts;

use App\Domain\Finance\Enums\ReceiptTypeEnum;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class ReceiptTypeCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ReceiptTypeEnum
    {
        return ReceiptTypeEnum::fromId($value !== null ? (int) $value : null);
    }

    public function set($model, string $key, $value, array $attributes): array
    {
        $id = is_int($value) ? $value : null;

        return [$key => $id];
    }
}
