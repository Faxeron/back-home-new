<?php

namespace App\Domain\Catalog\Models;

use App\Domain\Catalog\Traits\HasCatalogSlug;
use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Common\Traits\BelongsToTenant;
use App\Domain\Common\Traits\HasCreator;
use App\Domain\Common\Traits\HasUpdater;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use BelongsToCompany;
    use HasCreator;
    use HasUpdater;
    use HasCatalogSlug;

    protected $connection = 'legacy_new';

    protected $table = 'product_categories';

    protected $guarded = [];

    protected $casts = [
        'sort_order' => 'int',
        'is_active' => 'bool',
        'is_global' => 'bool',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(ProductSubcategory::class, 'category_id');
    }
}
