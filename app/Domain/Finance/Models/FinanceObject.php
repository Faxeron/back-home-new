<?php

declare(strict_types=1);

namespace App\Domain\Finance\Models;

use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Common\Traits\BelongsToTenant;
use App\Domain\Common\Traits\HasCreator;
use App\Domain\Common\Traits\HasUpdater;
use App\Domain\CRM\Models\Contract;
use App\Domain\CRM\Models\Counterparty;
use App\Domain\Finance\Enums\FinanceObjectStatus;
use App\Domain\Finance\Enums\FinanceObjectType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FinanceObject extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use BelongsToCompany;
    use HasCreator;
    use HasUpdater;

    protected $connection = 'legacy_new';

    protected $table = 'finance_objects';

    protected $guarded = [];

    protected $casts = [
        'type' => FinanceObjectType::class,
        'status' => FinanceObjectStatus::class,
        'date_from' => 'date',
        'date_to' => 'date',
    ];

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, 'counterparty_id');
    }

    public function legalContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'legal_contract_id');
    }

    public function typeDefinition(): BelongsTo
    {
        return $this->belongsTo(FinanceObjectTypeDefinition::class, 'type', 'key');
    }

    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class, 'finance_object_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'finance_object_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class, 'finance_object_id');
    }

    public function spendings(): HasMany
    {
        return $this->hasMany(Spending::class, 'finance_object_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(FinanceObjectAllocation::class, 'finance_object_id');
    }

    public function scopeOfTenantCompany($query, ?int $tenantId, ?int $companyId)
    {
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query;
    }

    public function canAcceptNewMoney(): bool
    {
        $status = $this->status instanceof FinanceObjectStatus
            ? $this->status->value
            : (string) $this->status;

        return !in_array($status, [
            FinanceObjectStatus::ARCHIVED->value,
            FinanceObjectStatus::CANCELED->value,
        ], true);
    }
}
