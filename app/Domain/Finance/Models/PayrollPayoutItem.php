<?php

namespace App\Domain\Finance\Models;

use App\Domain\CRM\Models\Contract;
use App\Domain\CRM\Models\ContractDocument;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollPayoutItem extends Model
{
    use HasFactory;

    protected $connection = 'legacy_new';

    protected $table = 'payroll_payout_items';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'float',
    ];

    public function payout(): BelongsTo
    {
        return $this->belongsTo(PayrollPayout::class, 'payout_id');
    }

    public function accrual(): BelongsTo
    {
        return $this->belongsTo(PayrollAccrual::class, 'accrual_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ContractDocument::class, 'contract_document_id');
    }

    public function spending(): BelongsTo
    {
        return $this->belongsTo(Spending::class, 'spending_id');
    }
}
