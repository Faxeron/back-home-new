<?php

namespace App\Domain\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Common\Models\User;

class ContractStatusChange extends Model
{
    use HasFactory;

    protected $connection = 'legacy_new';

    protected $table = 'contract_status_changes';

    protected $guarded = [];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function previousStatus(): BelongsTo
    {
        return $this->belongsTo(ContractStatus::class, 'previous_status_id');
    }

    public function newStatus(): BelongsTo
    {
        return $this->belongsTo(ContractStatus::class, 'new_status_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
