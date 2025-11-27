<?php

namespace App\Domain\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Counterparty extends Model
{
    use HasFactory;

    protected $connection = 'legacy_new';

    protected $table = 'counterparties';

    protected $guarded = [];
}
