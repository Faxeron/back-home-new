<?php

namespace App\Domain\Finance\Casts;

use App\Domain\Finance\Enums\PaymentMethodEnum;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\DB;

class PaymentMethodCast implements CastsAttributes
{
    private static ?array $cache = null;

    public function get($model, string $key, $value, array $attributes): PaymentMethodEnum
    {
        $code = $this->mapIdToCode($value);

        return PaymentMethodEnum::fromCode($code);
    }

    public function set($model, string $key, $value, array $attributes): array
    {
        if ($value instanceof PaymentMethodEnum) {
            $code = $value->code();
        } elseif (is_string($value)) {
            $code = strtoupper($value);
        } else {
            $code = null;
        }

        $id = $this->mapCodeToId($code);

        return [$key => $id];
    }

    private function mapIdToCode($id): ?string
    {
        if ($id === null) {
            return null;
        }

        $map = $this->getMap();

        return $map[$id] ?? null;
    }

    private function mapCodeToId(?string $code): ?int
    {
        if ($code === null) {
            return null;
        }

        $map = array_flip($this->getMap());

        return $map[$code] ?? null;
    }

    private function getMap(): array
    {
        if (self::$cache === null) {
            self::$cache = DB::connection('legacy_new')
                ->table('payment_methods')
                ->pluck('code', 'id')
                ->map(fn ($code) => strtoupper($code))
                ->all();
        }

        return self::$cache;
    }
}
