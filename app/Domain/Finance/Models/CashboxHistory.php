<?php

namespace App\Domain\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashboxHistory extends Model
{
    use HasFactory;

    protected $connection = 'legacy_new';

    protected $table = 'cashbox_history';

    protected $guarded = [];

    protected $casts = [
        'balance_after' => 'decimal:2',
    ];

    public function cashbox(): BelongsTo
    {
        return $this->belongsTo(CashBox::class, 'cashbox_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}
