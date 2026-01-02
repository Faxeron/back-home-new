<?php

namespace App\Domain\Catalog\Models;

use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Common\Traits\BelongsToTenant;
use App\Domain\Common\Traits\HasCreator;
use App\Domain\Common\Traits\HasUpdater;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'sort_order' => 'int',
        'price' => 'float',
        'price_sale' => 'float',
        'price_vendor' => 'float',
        'price_vendor_min' => 'float',
        'price_zakup' => 'float',
        'price_delivery' => 'float',
        'montaj' => 'float',
        'montaj_sebest' => 'float',
        'is_visible' => 'bool',
        'is_top' => 'bool',
        'is_new' => 'bool',
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

    public function kind(): BelongsTo
    {
        return $this->belongsTo(ProductKind::class, 'product_kind_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'unit_id');
    }

    public function description(): HasOne
    {
        return $this->hasOne(ProductDescription::class, 'product_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class, 'product_id')->orderBy('sort_order');
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class, 'product_id');
    }

    public function relations(): HasMany
    {
        return $this->hasMany(ProductRelation::class, 'product_id');
    }
}
