<?php

namespace App\Domain\Common\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicLead extends Model
{
    use HasFactory;

    protected $connection = 'legacy_new';

    protected $table = 'public_leads';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];
}
