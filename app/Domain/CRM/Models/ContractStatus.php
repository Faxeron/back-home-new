<?php

namespace App\Domain\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractStatus extends Model
{
    use HasFactory;

    protected $connection = 'legacy_new';

    protected $table = 'contract_statuses';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
