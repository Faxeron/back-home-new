<?php

namespace App\Domain\Catalog\Models;

use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Common\Traits\BelongsToTenant;
use App\Domain\Common\Traits\HasCreator;
use App\Domain\Common\Traits\HasUpdater;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use BelongsToCompany;
    use HasCreator;
    use HasUpdater;

    protected $connection = 'legacy_new';

    protected $table = 'products';

    protected $guarded = [];

    protected $casts = [
        'price' => 'float',
        'price_sale' => 'float',
        'price_vendor' => 'float',
        'price_zakup' => 'float',
        'delivery_price' => 'float',
        'montaj' => 'float',
        'montaj_sebest' => 'float',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(ProductSubcategory::class, 'sub_category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ProductBrand::class, 'brand_id');
    }
}
