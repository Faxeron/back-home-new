<?php

namespace App\Domain\Finance\Models;

use App\Domain\Common\Models\Company;
use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Common\Traits\HasCreator;
use App\Domain\Common\Traits\HasUpdater;
use App\Domain\Finance\Casts\MoneyCast;
use App\Domain\Finance\Models\Transaction;
use App\Domain\Finance\Models\CashBox;
use App\Domain\Finance\Models\SpendingFund;
use App\Domain\Finance\Models\SpendingItem;
use App\Domain\Finance\Models\FinanceAllocation;
use App\Domain\CRM\Models\Contract;
use App\Domain\CRM\Models\Counterparty;
use App\Domain\Common\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Spending extends Model
{
    use HasFactory;
    use BelongsToCompany;
    use HasCreator;
    use HasUpdater;

    protected $connection = 'legacy_new';

    protected $table = 'spendings';

    protected $guarded = [];

    protected $casts = [
        'sum' => MoneyCast::class,
        'created_at' => 'datetime',
        'payment_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function cashbox(): BelongsTo
    {
        return $this->belongsTo(CashBox::class, 'cashbox_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(SpendingFund::class, 'fond_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(SpendingItem::class, 'spending_item_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, 'counterparty_id');
    }

    public function spentToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'spent_to_user_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(FinanceAllocation::class, 'spending_id');
    }

    public function scopeOfContract($query, int $contractId)
    {
        return $query->where('contract_id', $contractId);
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
