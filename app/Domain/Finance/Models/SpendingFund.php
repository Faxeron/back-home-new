<?php

namespace App\Domain\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpendingFund extends Model
{
    use HasFactory;

    protected $connection = 'legacy_new';

    protected $table = 'spending_funds';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SpendingItem::class, 'fond_id');
    }
}
