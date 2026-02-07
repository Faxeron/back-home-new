<?php

namespace App\Services\Catalog;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CatalogSlugService
{
    public function makeBase(string $name, ?int $idFallback = null): string
    {
        $name = trim($name);
        $base = $name !== '' ? Str::slug($name, '-', 'ru') : '';

        if ($base === '') {
            return $idFallback ? "item-{$idFallback}" : 'item';
        }

        return $base;
    }

    public function uniqueForModel(string $modelClass, string $name, ?int $tenantId, ?int $companyId, ?int $ignoreId = null): string
    {
        /** @var Model $model */
        $model = new $modelClass();

        $connectionName = $model->getConnectionName() ?: config('database.default');
        $table = $model->getTable();

        return $this->uniqueForTable($connectionName, $table, $name, $tenantId, $companyId, $ignoreId);
    }

    public function uniqueForTable(string $connectionName, string $table, string $name, ?int $tenantId, ?int $companyId, ?int $ignoreId = null): string
    {
        $base = $this->makeBase($name, $ignoreId);

        $slug = $base;
        $suffix = 2;
        while ($this->exists($connectionName, $table, $slug, $tenantId, $companyId, $ignoreId)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function exists(string $connectionName, string $table, string $slug, ?int $tenantId, ?int $companyId, ?int $ignoreId = null): bool
    {
        $q = DB::connection($connectionName)->table($table)->where('slug', $slug);

        if ($tenantId === null) {
            $q->whereNull('tenant_id');
        } else {
            $q->where('tenant_id', $tenantId);
        }

        if ($companyId === null) {
            $q->whereNull('company_id');
        } else {
            $q->where('company_id', $companyId);
        }

        if ($ignoreId !== null) {
            $q->where('id', '!=', $ignoreId);
        }

        return $q->exists();
    }
}

