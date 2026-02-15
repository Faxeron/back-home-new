<?php

declare(strict_types=1);

namespace App\Domain\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceObjectTypeSetting extends Model
{
    protected $connection = 'legacy_new';

    protected $table = 'finance_object_type_settings';

    protected $guarded = [];

    protected $casts = [
        'is_enabled' => 'bool',
        'sort_order' => 'int',
    ];

    public function typeDefinition(): BelongsTo
    {
        return $this->belongsTo(FinanceObjectTypeDefinition::class, 'type_key', 'key');
    }
}
