<?php

namespace App\Domain\Catalog\Models;

use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Common\Traits\BelongsToTenant;
use App\Domain\Common\Traits\HasCreator;
use App\Domain\Common\Traits\HasUpdater;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProductAttributeDefinition extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use BelongsToCompany;
    use HasCreator;
    use HasUpdater;

    protected $connection = 'legacy_new';

    protected $table = 'product_attribute_definitions';

    protected $guarded = [];

    protected $casts = [
        'is_visible' => 'bool',
        'is_filterable' => 'bool',
        'sort_order' => 'int',
        'is_global' => 'bool',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $definition): void {
            $code = trim((string) ($definition->code ?? ''));
            if ($code !== '') {
                return;
            }

            $definition->code = self::makeUniqueCode(
                (string) ($definition->name ?? ''),
                $definition->tenant_id === null ? null : (int) $definition->tenant_id,
                $definition->company_id === null ? null : (int) $definition->company_id,
            );
        });
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class, 'attribute_id');
    }

    public function productKind(): BelongsTo
    {
        return $this->belongsTo(ProductKind::class, 'product_kind_id');
    }

    private static function makeUniqueCode(string $name, ?int $tenantId, ?int $companyId, ?int $excludeId = null): string
    {
        $base = Str::slug(trim($name), '-', 'ru');
        if ($base === '') {
            $base = 'attr';
        }

        $code = $base;
        $suffix = 2;
        while (self::codeExists($code, $tenantId, $companyId, $excludeId)) {
            $code = "{$base}-{$suffix}";
            $suffix++;
        }

        return $code;
    }

    private static function codeExists(string $code, ?int $tenantId, ?int $companyId, ?int $excludeId = null): bool
    {
        $query = self::query()->where('code', $code);

        if ($tenantId === null) {
            $query->whereNull('tenant_id');
        } else {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId === null) {
            $query->whereNull('company_id');
        } else {
            $query->where('company_id', $companyId);
        }

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
