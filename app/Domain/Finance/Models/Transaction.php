<?php

namespace App\Domain\Finance\Models;

use App\Domain\Common\Models\Company;
use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Common\Traits\HasCreator;
use App\Domain\Common\Traits\HasUpdater;
use App\Domain\CRM\Models\Counterparty;
use App\Domain\CRM\Models\Contract;
use App\Domain\Finance\Casts\MoneyCast;
use App\Domain\Finance\Casts\PaymentMethodCast;
use App\Domain\Finance\Casts\TransactionTypeCast;
use App\Domain\Finance\Models\CashboxHistory;
use App\Domain\Finance\Models\CashBox;
use App\Domain\Finance\Models\PaymentMethod;
use App\Domain\Finance\Models\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasFactory;
    use BelongsToCompany;
    use HasCreator;
    use HasUpdater;

    protected $connection = 'legacy_new';

    protected $table = 'transactions';

    protected $guarded = [];

    protected $casts = [
        'is_paid' => 'boolean',
        'is_completed' => 'boolean',
        'date_is_paid' => 'datetime',
        'date_is_completed' => 'datetime',
        'sum' => MoneyCast::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function cashbox(): BelongsTo
    {
        return $this->belongsTo(CashBox::class, 'cashbox_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, 'counterparty_id');
    }

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class, 'transaction_type_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class, 'transaction_id');
    }

    public function spending(): HasOne
    {
        return $this->hasOne(Spending::class, 'transaction_id');
    }

    public function cashboxHistory(): HasMany
    {
        return $this->hasMany(CashboxHistory::class, 'transaction_id');
    }

    public function scopeOfContract($query, int $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    public function scopeOnlyIncome($query)
    {
        return $query->where('sum', '>', 0);
    }

    public function scopeOnlyExpense($query)
    {
        return $query->where('sum', '<', 0);
    }

    public function scopeOfCashbox($query, int $cashboxId)
    {
        return $query->where('cashbox_id', $cashboxId);
    }
}
