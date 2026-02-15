<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Domain\Finance\Enums\FinanceObjectType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FinanceObjectTypeService
{
    /**
     * @return Collection<int, array{key: string, name: string, icon: string|null, sort_order: int, is_enabled: bool}>
     */
    public function listForCompany(int $tenantId, int $companyId, bool $includeDisabled = true): Collection
    {
        $this->ensureCompanySettings($tenantId, $companyId);

        $query = DB::connection('legacy_new')
            ->table('finance_object_types as t')
            ->leftJoin('finance_object_type_settings as s', function ($join) use ($tenantId, $companyId): void {
                $join->on('s.type_key', '=', 't.key')
                    ->where('s.tenant_id', '=', $tenantId)
                    ->where('s.company_id', '=', $companyId);
            })
            ->selectRaw('t.key as type_key')
            ->selectRaw('COALESCE(s.name_ru, t.default_name_ru) as display_name')
            ->selectRaw('COALESCE(s.icon, t.default_icon) as display_icon')
            ->selectRaw('COALESCE(s.sort_order, t.default_sort_order) as display_sort_order')
            ->selectRaw('COALESCE(s.is_enabled, 1) as enabled')
            ->orderByRaw('COALESCE(s.sort_order, t.default_sort_order)')
            ->orderBy('t.key');

        if (!$includeDisabled) {
            $query->whereRaw('COALESCE(s.is_enabled, 1) = 1');
        }

        return $query->get()->map(static fn ($row) => [
            'key' => (string) $row->type_key,
            'name' => (string) $row->display_name,
            'icon' => $row->display_icon ? (string) $row->display_icon : null,
            'sort_order' => (int) $row->display_sort_order,
            'is_enabled' => (bool) $row->enabled,
        ])->values();
    }

    public function assertTypeEnabledForCreation(string $typeKey, int $tenantId, int $companyId): void
    {
        $normalized = strtoupper(trim($typeKey));

        if (!in_array($normalized, FinanceObjectType::values(), true)) {
            throw new RuntimeException('Finance object type is not supported.');
        }

        if (!$this->typeExists($normalized)) {
            throw new RuntimeException('Finance object type is not configured.');
        }

        $this->ensureCompanySettings($tenantId, $companyId);

        $enabled = DB::connection('legacy_new')
            ->table('finance_object_type_settings')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('type_key', $normalized)
            ->value('is_enabled');

        if (!(bool) $enabled) {
            throw new RuntimeException('Finance object type is disabled for current company.');
        }
    }

    /**
     * @param array{is_enabled?: bool, name_ru?: string|null, icon?: string|null, sort_order?: int|null} $payload
     * @return array{key: string, name: string, icon: string|null, sort_order: int, is_enabled: bool}
     */
    public function updateSettings(int $tenantId, int $companyId, string $typeKey, array $payload): array
    {
        $normalized = strtoupper(trim($typeKey));

        if (!in_array($normalized, FinanceObjectType::values(), true)) {
            throw new RuntimeException('Finance object type is not supported.');
        }

        if (!$this->typeExists($normalized)) {
            throw new RuntimeException('Finance object type is not configured.');
        }

        $this->ensureCompanySettings($tenantId, $companyId);

        $current = DB::connection('legacy_new')
            ->table('finance_object_type_settings')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('type_key', $normalized)
            ->first(['is_enabled']);

        if (!$current) {
            throw new RuntimeException('Finance object type settings not found.');
        }

        $updates = [];

        if (array_key_exists('is_enabled', $payload)) {
            $nextEnabled = (bool) $payload['is_enabled'];
            $currentEnabled = (bool) $current->is_enabled;

            if (!$nextEnabled && $currentEnabled) {
                $enabledCount = DB::connection('legacy_new')
                    ->table('finance_object_type_settings')
                    ->where('tenant_id', $tenantId)
                    ->where('company_id', $companyId)
                    ->where('is_enabled', 1)
                    ->count();

                if ($enabledCount <= 1) {
                    throw new RuntimeException('At least one finance object type must remain enabled.');
                }
            }

            $updates['is_enabled'] = $nextEnabled;
        }

        if (array_key_exists('name_ru', $payload)) {
            $value = $payload['name_ru'];
            $updates['name_ru'] = is_string($value) && trim($value) !== '' ? trim($value) : null;
        }

        if (array_key_exists('icon', $payload)) {
            $value = $payload['icon'];
            $updates['icon'] = is_string($value) && trim($value) !== '' ? trim($value) : null;
        }

        if (array_key_exists('sort_order', $payload)) {
            $updates['sort_order'] = $payload['sort_order'] !== null ? (int) $payload['sort_order'] : null;
        }

        if ($updates !== []) {
            $updates['updated_at'] = now();
            DB::connection('legacy_new')
                ->table('finance_object_type_settings')
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->where('type_key', $normalized)
                ->update($updates);
        }

        $row = $this->listForCompany($tenantId, $companyId, true)->firstWhere('key', $normalized);
        if (!$row) {
            throw new RuntimeException('Failed to resolve updated type settings.');
        }

        return $row;
    }

    private function typeExists(string $typeKey): bool
    {
        return DB::connection('legacy_new')
            ->table('finance_object_types')
            ->where('key', $typeKey)
            ->exists();
    }

    private function ensureCompanySettings(int $tenantId, int $companyId): void
    {
        $types = DB::connection('legacy_new')
            ->table('finance_object_types')
            ->orderBy('default_sort_order')
            ->get(['key', 'default_sort_order']);

        if ($types->isEmpty()) {
            throw new RuntimeException('Finance object types catalog is empty.');
        }

        $existing = DB::connection('legacy_new')
            ->table('finance_object_type_settings')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->pluck('type_key')
            ->all();

        $existingMap = array_fill_keys(array_map('strval', $existing), true);

        $rows = [];
        $now = now();
        foreach ($types as $type) {
            $key = (string) $type->key;
            if (isset($existingMap[$key])) {
                continue;
            }

            $rows[] = [
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'type_key' => $key,
                'is_enabled' => $key !== 'LEGACY_IMPORT',
                'name_ru' => null,
                'icon' => null,
                'sort_order' => (int) $type->default_sort_order,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::connection('legacy_new')->table('finance_object_type_settings')->insert($rows);
        }
    }
}
