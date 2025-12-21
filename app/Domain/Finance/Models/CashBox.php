<?php

namespace App\Domain\Finance\Models;

use App\Domain\Common\Models\Company;
use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Finance\Models\CashTransfer;
use App\Domain\Finance\Models\CashboxHistory;
use App\Domain\Finance\Models\Receipt;
use App\Domain\Finance\Models\Spending;
use App\Domain\Finance\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashBox extends Model
{
    use HasFactory;
    use BelongsToCompany;

    protected $connection = 'legacy_new';

    protected $table = 'cashboxes';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'cashbox_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class, 'cashbox_id');
    }

    public function spendings(): HasMany
    {
        return $this->hasMany(Spending::class, 'cashbox_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(CashboxHistory::class, 'cashbox_id');
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(CashTransfer::class, 'from_cashbox_id');
    }

    public function transfersTo(): HasMany
    {
        return $this->hasMany(CashTransfer::class, 'to_cashbox_id');
    }
}
