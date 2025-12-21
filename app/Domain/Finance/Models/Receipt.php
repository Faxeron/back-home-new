<?php

namespace App\Domain\Finance\Models;

use App\Domain\Common\Models\Company;
use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Common\Traits\HasCreator;
use App\Domain\Common\Traits\HasUpdater;
use App\Domain\CRM\Models\Contract;
use App\Domain\CRM\Models\Counterparty;
use App\Domain\Finance\Casts\MoneyCast;
use App\Domain\Finance\Models\CashBox;
use App\Domain\Finance\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    use HasFactory;
    use BelongsToCompany;
    use HasCreator;
    use HasUpdater;

    protected $connection = 'legacy_new';

    protected $table = 'receipts';

    protected $guarded = [];

    protected $casts = [
        'sum' => MoneyCast::class,
        'payment_date' => 'date',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function cashbox(): BelongsTo
    {
        return $this->belongsTo(CashBox::class, 'cashbox_id');
    }

    public function cashBox(): BelongsTo
    {
        // legacy alias until all callers are updated
        return $this->cashbox();
    }

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, 'counterparty_id');
    }

    public function scopeOfContract($query, int $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    public function scopeOfCashbox($query, int $cashboxId)
    {
        return $query->where('cashbox_id', $cashboxId);
    }
}
