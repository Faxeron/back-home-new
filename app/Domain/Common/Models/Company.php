<?php

namespace App\Domain\Common\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $connection = 'legacy_new';

    protected $table = 'companies';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
