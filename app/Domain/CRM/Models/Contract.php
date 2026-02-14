<?php

namespace App\Domain\CRM\Models;

use App\Domain\Finance\Enums\ContractSystemStatusEnum;
use App\Domain\Common\Models\User;
use App\Domain\Finance\Models\FinanceObject;
use App\Domain\Finance\Models\Receipt;
use App\Domain\Finance\Models\Spending;
use App\Domain\Finance\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Domain\CRM\Models\ContractStatusChange;

class Contract extends Model
{
    use HasFactory;

    protected $connection = 'legacy_new';

    protected $table = 'contracts';

    protected $guarded = [];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'contract_date' => 'date',
        'work_start_date' => 'date',
        'work_end_date' => 'date',
        'work_done_date' => 'date',
        'system_status_code' => ContractSystemStatusEnum::class,
        'template_product_type_ids' => 'array',
    ];

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, 'counterparty_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ContractStatus::class, 'contract_status_id');
    }

    public function saleType(): BelongsTo
    {
        return $this->belongsTo(SaleType::class, 'sale_type_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function measurer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'measurer_id');
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function financeObject(): BelongsTo
    {
        return $this->belongsTo(FinanceObject::class, 'finance_object_id');
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

    public function statusChanges(): HasMany
    {
        return $this->hasMany(ContractStatusChange::class, 'contract_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class, 'contract_template_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class, 'contract_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ContractDocument::class, 'contract_id');
    }

    public function currentDocument(): HasOne
    {
        return $this->hasOne(ContractDocument::class, 'contract_id')
            ->where('is_current', true);
    }
}
