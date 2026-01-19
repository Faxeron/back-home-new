<?php

namespace App\Domain\CRM\Models;

use App\Domain\Catalog\Models\ProductType;
use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Common\Traits\BelongsToTenant;
use App\Domain\Common\Traits\HasCreator;
use App\Domain\Common\Traits\HasUpdater;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContractTemplate extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use BelongsToCompany;
    use HasCreator;
    use HasUpdater;

    protected $connection = 'legacy_new';

    protected $table = 'contract_templates';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'bool',
        'advance_product_type_ids' => 'array',
    ];

    public function productTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductType::class,
            'contract_template_product_types',
            'template_id',
            'product_type_id',
        );
    }
}
