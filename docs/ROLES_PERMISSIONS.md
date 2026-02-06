# Roles & Permissions (Права и роли)

Назначение
- Управление доступом к страницам и действиям внутри системы.
- Роли: `superadmin`, `admin`, `manager`, `measurer`, `worker`.
- Один пользователь может иметь несколько ролей.

Модель данных (legacy_new)
- `permissions` — список прав (resource + action).
- `role_permissions` — связь роль - право.
- `role_scopes` — области видимости (own/company/tenant/all).
- `roles`, `role_users` — роли и назначения.

Actions
- `view`, `create`, `edit`, `delete`, `export`, `assign`, `finance`.

Resources (пример)
- `estimates`, `estimate_templates`, `contracts`, `contract_templates`, `measurements`, `installations`.
- `products`, `pricebook`, `clients`, `finance`, `payroll`, `knowledge`, `dev_control`.
- Settings: `settings.cash_boxes`, `settings.companies`, `settings.spending_funds`, `settings.spending_items`,
  `settings.contract_statuses`, `settings.transaction_types`, `settings.sale_types`,
  `settings.cities`, `settings.districts`, `settings.payroll`, `settings.margin`, `settings.roles`.

API (settings)
- GET `/api/settings/roles-permissions`
- PATCH `/api/settings/roles-permissions/roles/{role}`
- PATCH `/api/settings/roles-permissions/users/{user}`

Frontend
- `/settings/roles-permissions`

Важно
- `admin` и `superadmin` всегда имеют полный доступ (изменения в UI заблокированы).
- После изменения ролей требуется повторный вход.

## REALITY STATUS
- Реально реализовано: ACL через таблицы `roles/role_users/permissions`, эндпоинты roles-permissions.
- Легаси: часть прав/ресурсов наследована из старой схемы.
- Не сделано: аудит изменений ACL и история назначений.
