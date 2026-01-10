<?php

namespace App\Domain\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceAuditLog extends Model
{
    use HasFactory;

    protected $connection = 'legacy_new';

    protected $table = 'finance_audit_logs';

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];
}
