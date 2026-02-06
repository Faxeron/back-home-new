<?php

namespace App\Support\Permissions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PermissionRegistry
{
    public const ACTIONS = [
        'view' => 'Просмотр',
        'create' => 'Создание',
        'edit' => 'Редактирование',
        'delete' => 'Удаление',
        'export' => 'Экспорт',
        'assign' => 'Назначение',
        'finance' => 'Финансы',
    ];

    public const RESOURCES = [
        'estimates' => 'Сметы',
        'estimate_templates' => 'Шаблоны смет',
        'contracts' => 'Договоры',
        'dashboard.total_sales' => 'Дашборд: Total Sales',
        'dashboard.employee' => 'Дашборд: Сотрудник',
        'contract_templates' => 'Шаблоны договоров',
        'measurements' => 'Замеры',
        'installations' => 'Монтажи',
        'products' => 'Товары',
        'pricebook' => 'Прайс',
        'clients' => 'Клиенты',
        'finance' => 'Финансы',
        'payroll' => 'ФОТ',
        'knowledge' => 'База знаний',
        'settings.cash_boxes' => 'Кассы',
        'settings.companies' => 'Компании',
        'settings.spending_funds' => 'Фонды расходов',
        'settings.spending_items' => 'Статьи расходов',
        'settings.contract_statuses' => 'Статусы договоров',
        'settings.transaction_types' => 'Типы транзакций',
        'settings.sale_types' => 'Типы продаж',
        'settings.cities' => 'Города',
        'settings.districts' => 'Районы',
        'settings.payroll' => 'Правила менеджеров',
        'settings.margin' => 'Настройки маржи',
        'settings.roles' => 'Права и роли',
        'dev_control' => 'Dev Control',
    ];

    public static function resources(): array
    {
        return collect(self::RESOURCES)
            ->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label])
            ->values()
            ->all();
    }

    public static function actions(): array
    {
        return collect(self::ACTIONS)
            ->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label])
            ->values()
            ->all();
    }

    public static function sync(): void
    {
        $schema = Schema::connection('legacy_new');
        if (!$schema->hasTable('permissions')) {
            return;
        }

        $db = DB::connection('legacy_new');
        $now = now();

        foreach (self::RESOURCES as $resource => $resourceLabel) {
            foreach (self::ACTIONS as $action => $actionLabel) {
                $code = "{$resource}.{$action}";
                $name = "{$resourceLabel}: {$actionLabel}";

                $db->table('permissions')->updateOrInsert(
                    ['code' => $code],
                    [
                        'resource' => $resource,
                        'action' => $action,
                        'name' => $name,
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }
}
