<?php

namespace App\Domain\System\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DevControl extends Model
{
    use HasFactory;

    protected $connection = 'legacy_new';

    protected $table = 'dev_control';

    protected $guarded = [];
}
