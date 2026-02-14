<?php

declare(strict_types=1);

namespace App\Domain\Finance\Models;

use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Common\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceObjectAllocation extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use BelongsToCompany;

    protected $connection = 'legacy_new';

    protected $table = 'finance_object_allocations';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'float',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function financeObject(): BelongsTo
    {
        return $this->belongsTo(FinanceObject::class, 'finance_object_id');
    }
}

