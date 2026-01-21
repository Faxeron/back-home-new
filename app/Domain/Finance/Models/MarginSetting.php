<?php

namespace App\Domain\Finance\Models;

use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Common\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarginSetting extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use BelongsToCompany;

    protected $connection = 'legacy_new';

    protected $table = 'margin_settings';

    protected $guarded = [];

    protected $casts = [
        'red_max' => 'float',
        'orange_max' => 'float',
    ];
}
