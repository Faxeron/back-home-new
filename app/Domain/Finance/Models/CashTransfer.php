<?php

namespace App\Domain\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransfer extends Model
{
    use HasFactory;

    protected $connection = 'legacy_new';

    protected $table = 'cash_transfers';

    protected $guarded = [];

    protected $casts = [
        'sum' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Common\Models\Company::class, 'company_id');
    }

    public function fromCashBox(): BelongsTo
    {
        return $this->belongsTo(CashBox::class, 'from_cash_box_id');
    }

    public function toCashBox(): BelongsTo
    {
        return $this->belongsTo(CashBox::class, 'to_cash_box_id');
    }

    public function transactionOut(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_out_id');
    }

    public function transactionIn(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_in_id');
    }
}
