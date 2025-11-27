<?php

namespace App\Domain\CRM\Models;

use App\Domain\Finance\Enums\ContractSystemStatusEnum;
use App\Domain\Finance\Models\Receipt;
use App\Domain\Finance\Models\Spending;
use App\Domain\Finance\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory;

    protected $connection = 'legacy_new';

    protected $table = 'contracts';

    protected $guarded = [];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'work_start_date' => 'date',
        'work_end_date' => 'date',
        'system_status_code' => ContractSystemStatusEnum::class,
    ];

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, 'counterparty_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ContractStatus::class, 'contract_status_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'contract_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class, 'contract_id');
    }

    public function spendings(): HasMany
    {
        return $this->hasMany(Spending::class, 'contract_id');
    }
}
