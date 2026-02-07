<?php

namespace App\Domain\Catalog\Traits;

use App\Services\Catalog\CatalogSlugService;
use Illuminate\Database\Eloquent\Model;

trait HasCatalogSlug
{
    protected static function bootHasCatalogSlug(): void
    {
        static::saving(function (Model $model): void {
            // If slug is set explicitly, do not overwrite it.
            $slug = trim((string) ($model->getAttribute('slug') ?? ''));
            if ($slug !== '') {
                return;
            }

            $name = (string) ($model->getAttribute('name') ?? '');
            $tenantIdRaw = $model->getAttribute('tenant_id');
            $companyIdRaw = $model->getAttribute('company_id');

            $tenantId = $tenantIdRaw === null ? null : (int) $tenantIdRaw;
            $companyId = $companyIdRaw === null ? null : (int) $companyIdRaw;

            $service = app(CatalogSlugService::class);
            $generated = $service->uniqueForModel($model::class, $name, $tenantId, $companyId, $model->getKey() ? (int) $model->getKey() : null);

            $model->setAttribute('slug', $generated);
        });
    }
}

