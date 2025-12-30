<?php

namespace App\Domain\Estimates\Models;

use App\Domain\Catalog\Models\Product;
use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Common\Traits\BelongsToTenant;
use App\Domain\Common\Traits\HasCreator;
use App\Domain\Common\Traits\HasUpdater;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateItem extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use BelongsToCompany;
    use HasCreator;
    use HasUpdater;

    protected $connection = 'legacy_new';

    protected $table = 'estimate_items';

    protected $guarded = [];

    protected $casts = [
        'qty' => 'float',
        'qty_auto' => 'float',
        'qty_manual' => 'float',
        'price' => 'float',
        'total' => 'float',
        'sort_order' => 'int',
    ];

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class, 'estimate_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(EstimateGroup::class, 'group_id');
    }
}
