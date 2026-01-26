<?php

namespace App\Domain\Finance\Models;

use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Common\Traits\BelongsToTenant;
use App\Domain\CRM\Models\Contract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceAllocation extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use BelongsToCompany;

    protected $connection = 'legacy_new';

    protected $table = 'finance_allocations';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'float',
    ];

    public function spending(): BelongsTo
    {
        return $this->belongsTo(Spending::class, 'spending_id');
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class, 'receipt_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }
}
