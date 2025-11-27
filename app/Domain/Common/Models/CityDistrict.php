<?php

namespace App\Domain\Common\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CityDistrict extends Model
{
    use HasFactory;

    protected $connection = 'legacy_new';

    protected $table = 'cities_districts';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
