<?php

namespace App\Domain\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleType extends Model
{
    use HasFactory;

    protected $connection = 'legacy_new';

    protected $table = 'sale_types';

    protected $guarded = [];
}
