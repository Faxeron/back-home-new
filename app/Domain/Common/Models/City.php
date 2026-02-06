<?php

namespace App\Domain\Common\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class City extends Model
{
    use HasFactory;

    protected $connection = 'legacy_new';

    protected $table = 'cities';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (self $city): void {
            $source = (string) ($city->slug ?: $city->name);
            $city->slug = self::makeUniqueSlug($source, $city->tenant_id);
        });

        static::updating(function (self $city): void {
            if ($city->isDirty('name')) {
                $city->slug = self::makeUniqueSlug((string) $city->name, $city->tenant_id, $city->id);
            }
        });
    }

    private static function makeUniqueSlug(string $source, ?int $tenantId = null, ?int $excludeId = null): string
    {
        $base = Str::slug($source, '-', 'ru');
        if ($base === '') {
            $base = 'item';
        }

        $slug = $base;
        $suffix = 2;

        $query = self::query()
            ->where('slug', $slug)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId));
        if ($tenantId === null) {
            $query->whereNull('tenant_id');
        } else {
            $query->where('tenant_id', $tenantId);
        }

        while ($query->exists()) {
            $slug = $base . '-' . $suffix;
            $suffix++;
            $query = self::query()
                ->where('slug', $slug)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId));
            if ($tenantId === null) {
                $query->whereNull('tenant_id');
            } else {
                $query->where('tenant_id', $tenantId);
            }
        }

        return $slug;
    }
}
