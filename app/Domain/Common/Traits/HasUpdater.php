<?php

namespace App\Domain\Common\Traits;

use App\Domain\Common\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasUpdater
{
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
