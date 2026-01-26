<?php

namespace App\Domain\Finance\Models;

use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Common\Traits\BelongsToTenant;
use App\Domain\Common\Models\User;
use App\Domain\CRM\Models\Contract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPayout extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use BelongsToCompany;

    protected $connection = 'legacy_new';

    protected $table = 'payroll_payouts';

    protected $guarded = [];

    protected $casts = [
        'payout_date' => 'date',
        'total_amount' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cashbox(): BelongsTo
    {
        return $this->belongsTo(CashBox::class, 'cashbox_id');
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(SpendingFund::class, 'fund_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(SpendingItem::class, 'spending_item_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollPayoutItem::class, 'payout_id');
    }
}
