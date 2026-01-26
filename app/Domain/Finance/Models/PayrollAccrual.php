<?php

namespace App\Domain\Finance\Models;

use App\Domain\Common\Models\User;
use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Common\Traits\BelongsToTenant;
use App\Domain\CRM\Models\Contract;
use App\Domain\CRM\Models\ContractDocument;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollAccrual extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use BelongsToCompany;

    protected $connection = 'legacy_new';

    protected $table = 'payroll_accruals';

    protected $guarded = [];

    protected $casts = [
        'base_amount' => 'float',
        'percent' => 'float',
        'amount' => 'float',
        'paid_amount' => 'float',
        'cancelled_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ContractDocument::class, 'contract_document_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(PayrollRule::class, 'rule_id');
    }
}
