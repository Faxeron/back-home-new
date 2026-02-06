# Public API Master Plan

## Цель
Построить публичный API для сайта, не смешивая его с внутренним ERP API. Источник данных — ERP (БД `legacy_new`).

## Текущее состояние
- Реализовано: `GET /api/public/cities`, `GET /api/public/companies`, `GET /api/public/products`, `GET /api/public/products/{slug}`, `POST /api/public/leads`.
- Только `tenant_id=1` (публичный сайт).
- Прайс для public API читается из `product_company_prices` (company-aware).

## План (дальше)
### Шаг 1. Анализ БД
(см. секцию таблиц ниже)

### Шаг 2. DTO (контракт для сайта)
- CityDTO, CompanyDTO, ProductCardDTO, ProductPageDTO.

### Шаг 3. Public API Endpoints
- `GET /api/public/cities` (сделано)
- `GET /api/public/companies` (сделано)
- `GET /api/public/products` (сделано)
- `GET /api/public/products/{slug}` (сделано)
- `POST /api/public/leads` (сделано)

### Шаг 4. Правила
- Только `tenant_id=1`.
- Только активные товары: `is_visible = 1` и `archived_at IS NULL`.
- Обязательный контекст `company_id` или `city` для всех product endpoints.
- Кэширование ответов (Cache-Control + Cache::remember с ключами, включающими company/city).
- Если для company нет цены, `price_sale = null` (price fields = null).

### Шаг 5. Архитектура
- Модуль: `app/Modules/PublicApi/*`.

### Шаг 6. Риски
- Привязка `city -> company` логическая (FK нет).
- `slug` может быть пустым в старых данных.
- Публичные цены зависят от полноты `product_company_prices` (нужен контроль покрытия).

## Таблицы (кратко)
- `cities`, `companies`, `products`, `product_descriptions`, `product_media`,
  `product_categories`, `product_subcategories`, `product_brands`, `product_types`, `product_kinds`, `product_units`,
  `product_attribute_definitions`, `product_attribute_values`, `product_relations`,
  `product_company_prices`, `public_leads`.

## REALITY STATUS
- Реально реализовано: cities/companies/products (list + slug), leads, company-aware pricing из `product_company_prices`.
- Легаси: ранее операционные цены хранились в `products`, но миграцией `2026_02_06_000004_drop_operational_prices_from_products` эти колонки удалены. Public API использует только `product_company_prices`.
- Не сделано: автоматическая проверка покрытия `product_company_prices` и наличия ненулевых цен для public (пока только artisan-команды).
