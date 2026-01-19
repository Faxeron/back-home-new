<?php

namespace App\Domain\CRM\Models;

use App\Domain\Catalog\Models\Product;
use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Common\Traits\BelongsToTenant;
use App\Domain\Common\Traits\HasCreator;
use App\Domain\Common\Traits\HasUpdater;
use App\Domain\Estimates\Models\EstimateItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractItem extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use BelongsToCompany;
    use HasCreator;
    use HasUpdater;

    protected $connection = 'legacy_new';

    protected $table = 'contract_items';

    protected $guarded = [];

    protected $casts = [
        'qty' => 'decimal:3',
        'price' => 'decimal:2',
        'total' => 'decimal:2',
        'sort_order' => 'int',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function estimateItem(): BelongsTo
    {
        return $this->belongsTo(EstimateItem::class, 'estimate_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
