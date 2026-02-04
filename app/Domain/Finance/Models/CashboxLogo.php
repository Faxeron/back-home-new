<?php

namespace App\Domain\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashboxLogo extends Model
{
    use HasFactory;

    protected $connection = 'legacy_new';

    protected $table = 'cashbox_logos';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
