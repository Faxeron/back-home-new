<?php

namespace App\Domain\CRM\Models;

use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Common\Traits\BelongsToTenant;
use App\Domain\Common\Traits\HasCreator;
use App\Domain\Common\Traits\HasUpdater;
use App\Domain\Estimates\Models\Estimate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractGroup extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use BelongsToCompany;
    use HasCreator;
    use HasUpdater;

    protected $connection = 'legacy_new';

    protected $table = 'contract_groups';

    protected $guarded = [];

    protected $casts = [
        'contract_date' => 'date',
        'installation_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class, 'estimate_id');
    }

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, 'counterparty_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ContractStatus::class, 'contract_status_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'contract_group_id');
    }
}
