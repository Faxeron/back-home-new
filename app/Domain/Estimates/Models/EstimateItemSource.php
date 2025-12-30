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

class EstimateItemSource extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use BelongsToCompany;
    use HasCreator;
    use HasUpdater;

    protected $connection = 'legacy_new';

    protected $table = 'estimate_item_sources';

    protected $guarded = [];

    protected $casts = [
        'qty_per_unit' => 'float',
        'root_qty' => 'float',
        'qty_total' => 'float',
    ];

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class, 'estimate_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function originProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'origin_product_id');
    }
}
