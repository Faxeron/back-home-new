<?php

namespace App\Domain\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Counterparty extends Model
{
    use HasFactory;

    protected $connection = 'legacy_new';

    protected $table = 'counterparties';

    protected $guarded = [];

    public function setPhoneAttribute($value): void
    {
        $this->attributes['phone'] = $value;
        $this->attributes['phone_normalized'] = self::normalizePhone($value);
    }

    public static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 10) {
            $digits = '7' . $digits;
        } elseif (strlen($digits) === 11 && $digits[0] === '8') {
            $digits = '7' . substr($digits, 1);
        }

        return $digits;
    }

    public function individual(): HasOne
    {
        return $this->hasOne(CounterpartyIndividual::class, 'counterparty_id');
    }

    public function company(): HasOne
    {
        return $this->hasOne(CounterpartyCompany::class, 'counterparty_id');
    }
}
