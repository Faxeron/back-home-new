<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Common\Traits\BelongsToTenant;
use App\Domain\Common\Traits\HasCreator;
use App\Domain\Common\Traits\HasUpdater;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCompanyPrice extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use BelongsToCompany;
    use HasCreator;
    use HasUpdater;

    protected $connection = 'legacy_new';

    protected $table = 'product_company_prices';

    protected $guarded = [];

    protected $casts = [
        'price' => 'float',
        'price_sale' => 'float',
        'price_delivery' => 'float',
        'montaj' => 'float',
        'montaj_sebest' => 'float',
        'is_active' => 'bool',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
