<?php

namespace App\Domain\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpendingItem extends Model
{
    use HasFactory;

    protected $connection = 'legacy_new';

    protected $table = 'spending_items';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function fund(): BelongsTo
    {
        return $this->belongsTo(SpendingFund::class, 'fond_id');
    }

    public function cashflowItem(): BelongsTo
    {
        return $this->belongsTo(CashflowItem::class, 'cashflow_item_id');
    }
}
