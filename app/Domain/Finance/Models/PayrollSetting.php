<?php

namespace App\Domain\Finance\Models;

use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Common\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use BelongsToCompany;

    protected $connection = 'legacy_new';

    protected $table = 'payroll_settings';

    protected $guarded = [];

    protected $casts = [
        'manager_fixed' => 'float',
        'manager_percent' => 'float',
        'measurer_fixed' => 'float',
        'measurer_percent' => 'float',
    ];
}
