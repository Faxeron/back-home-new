<?php

namespace App\Domain\CRM\Models;

use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Common\Traits\BelongsToTenant;
use App\Domain\Common\Traits\HasCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractDocument extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use BelongsToCompany;
    use HasCreator;

    protected $connection = 'legacy_new';

    protected $table = 'contract_documents';

    protected $guarded = [];

    protected $casts = [
        'is_current' => 'bool',
        'generated_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class, 'template_id');
    }
}
