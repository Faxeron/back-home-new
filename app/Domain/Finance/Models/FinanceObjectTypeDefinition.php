<?php

declare(strict_types=1);

namespace App\Domain\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceObjectTypeDefinition extends Model
{
    protected $connection = 'legacy_new';

    protected $table = 'finance_object_types';

    protected $guarded = [];

    public function settings(): HasMany
    {
        return $this->hasMany(FinanceObjectTypeSetting::class, 'type_key', 'key');
    }
}
