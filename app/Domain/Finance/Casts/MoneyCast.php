<?php

namespace App\Domain\Finance\Casts;

use App\Domain\Finance\ValueObjects\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class MoneyCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): Money
    {
        return Money::fromNumeric($value);
    }

    public function set($model, string $key, $value, array $attributes): array
    {
        $money = $value instanceof Money ? $value : Money::fromNumeric($value);

        return [$key => $money->amount()];
    }
}
