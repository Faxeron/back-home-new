<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'legacy_new';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('finance_object_types')) {
            $schema->create('finance_object_types', function (Blueprint $table): void {
                $table->id();
                $table->string('key', 32)->unique();
                $table->string('default_name_ru', 128);
                $table->string('default_name_en', 128)->nullable();
                $table->string('default_icon', 64)->nullable();
                $table->integer('default_sort_order')->default(100);
                $table->boolean('is_system')->default(true);
                $table->timestamps();
            });
        }

        if (!$schema->hasTable('finance_object_type_settings')) {
            $schema->create('finance_object_type_settings', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('company_id');
                $table->string('type_key', 32);
                $table->boolean('is_enabled')->default(true);
                $table->string('name_ru', 128)->nullable();
                $table->string('icon', 64)->nullable();
                $table->integer('sort_order')->nullable();
                $table->timestamps();

                $table->unique(
                    ['tenant_id', 'company_id', 'type_key'],
                    'finance_object_type_settings_company_type_unique'
                );
                $table->index(
                    ['tenant_id', 'company_id', 'is_enabled'],
                    'finance_object_type_settings_company_enabled_idx'
                );
                $table->index(
                    ['tenant_id', 'company_id', 'sort_order'],
                    'finance_object_type_settings_company_sort_idx'
                );
                $table->foreign('type_key', 'finance_object_type_settings_type_key_fk')
                    ->references('key')
                    ->on('finance_object_types')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        }

        $this->seedTypeCatalog();
        $this->seedCompanySettings();
        $this->convertFinanceObjectsTypeColumn();
    }

    public function down(): void
    {
        // forward-only migration
    }

    /**
     * @return array<int, array{key: string, default_name_ru: string, default_name_en: string|null, default_icon: string|null, default_sort_order: int, is_system: bool}>
     */
    private function defaultTypes(): array
    {
        return [
            [
                'key' => 'CONTRACT',
                'default_name_ru' => 'Договоры',
                'default_name_en' => 'Contracts',
                'default_icon' => 'lucide:briefcase',
                'default_sort_order' => 10,
                'is_system' => true,
            ],
            [
                'key' => 'PROJECT',
                'default_name_ru' => 'Проекты',
                'default_name_en' => 'Projects',
                'default_icon' => 'lucide:folder-kanban',
                'default_sort_order' => 20,
                'is_system' => true,
            ],
            [
                'key' => 'EVENT',
                'default_name_ru' => 'Мероприятия',
                'default_name_en' => 'Events',
                'default_icon' => 'lucide:calendar-days',
                'default_sort_order' => 30,
                'is_system' => true,
            ],
            [
                'key' => 'ORDER',
                'default_name_ru' => 'Заказы',
                'default_name_en' => 'Orders',
                'default_icon' => 'lucide:shopping-bag',
                'default_sort_order' => 40,
                'is_system' => true,
            ],
            [
                'key' => 'SUBSCRIPTION',
                'default_name_ru' => 'Подписки',
                'default_name_en' => 'Subscriptions',
                'default_icon' => 'lucide:repeat',
                'default_sort_order' => 50,
                'is_system' => true,
            ],
            [
                'key' => 'TENDER',
                'default_name_ru' => 'Тендеры',
                'default_name_en' => 'Tenders',
                'default_icon' => 'lucide:file-check',
                'default_sort_order' => 60,
                'is_system' => true,
            ],
            [
                'key' => 'SERVICE',
                'default_name_ru' => 'Сервис',
                'default_name_en' => 'Service',
                'default_icon' => 'lucide:wrench',
                'default_sort_order' => 70,
                'is_system' => true,
            ],
            [
                'key' => 'INTERNAL',
                'default_name_ru' => 'Внутренние',
                'default_name_en' => 'Internal',
                'default_icon' => 'lucide:building-2',
                'default_sort_order' => 80,
                'is_system' => true,
            ],
            [
                'key' => 'LEGACY_IMPORT',
                'default_name_ru' => 'Legacy Import',
                'default_name_en' => 'Legacy Import',
                'default_icon' => 'lucide:archive',
                'default_sort_order' => 999,
                'is_system' => true,
            ],
        ];
    }

    private function seedTypeCatalog(): void
    {
        $db = DB::connection($this->connection);
        $now = now();

        foreach ($this->defaultTypes() as $row) {
            $exists = $db->table('finance_object_types')->where('key', $row['key'])->exists();
            if ($exists) {
                continue;
            }

            $db->table('finance_object_types')->insert([
                'key' => $row['key'],
                'default_name_ru' => $row['default_name_ru'],
                'default_name_en' => $row['default_name_en'],
                'default_icon' => $row['default_icon'],
                'default_sort_order' => $row['default_sort_order'],
                'is_system' => $row['is_system'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedCompanySettings(): void
    {
        $db = DB::connection($this->connection);

        $types = $db->table('finance_object_types')
            ->orderBy('default_sort_order')
            ->get(['key', 'default_sort_order']);

        if ($types->isEmpty()) {
            return;
        }

        $pairMap = [];

        $companyPairs = $db->table('companies')
            ->whereNotNull('tenant_id')
            ->get(['tenant_id', 'id as company_id']);

        foreach ($companyPairs as $pair) {
            $pairMap[$pair->tenant_id . '|' . $pair->company_id] = [
                'tenant_id' => (int) $pair->tenant_id,
                'company_id' => (int) $pair->company_id,
            ];
        }

        $objectPairs = $db->table('finance_objects')
            ->whereNotNull('tenant_id')
            ->whereNotNull('company_id')
            ->groupBy('tenant_id', 'company_id')
            ->get(['tenant_id', 'company_id']);

        foreach ($objectPairs as $pair) {
            $pairMap[$pair->tenant_id . '|' . $pair->company_id] = [
                'tenant_id' => (int) $pair->tenant_id,
                'company_id' => (int) $pair->company_id,
            ];
        }

        if ($pairMap === []) {
            return;
        }

        $now = now();

        foreach ($pairMap as $pair) {
            $existing = $db->table('finance_object_type_settings')
                ->where('tenant_id', $pair['tenant_id'])
                ->where('company_id', $pair['company_id'])
                ->pluck('type_key')
                ->all();

            $existingMap = array_fill_keys(array_map('strval', $existing), true);

            $rows = [];
            foreach ($types as $type) {
                $key = (string) $type->key;
                if (isset($existingMap[$key])) {
                    continue;
                }

                $rows[] = [
                    'tenant_id' => $pair['tenant_id'],
                    'company_id' => $pair['company_id'],
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
                $db->table('finance_object_type_settings')->insert($rows);
            }
        }
    }

    private function convertFinanceObjectsTypeColumn(): void
    {
        $schema = Schema::connection($this->connection);
        if (!$schema->hasTable('finance_objects') || !$schema->hasColumn('finance_objects', 'type')) {
            return;
        }

        $driver = DB::connection($this->connection)->getDriverName();

        if ($driver === 'mysql') {
            DB::connection($this->connection)->statement(
                'ALTER TABLE finance_objects MODIFY COLUMN type VARCHAR(32) NOT NULL'
            );
            return;
        }

        if ($driver === 'pgsql') {
            DB::connection($this->connection)->statement(
                'ALTER TABLE finance_objects ALTER COLUMN type TYPE VARCHAR(32) USING type::text'
            );
        }
    }
};
