# Public API Master Plan

## Цель
Построить публичный API для сайта, не смешивая его с внутренним ERP API. Источник данных — ERP (БД `legacy_new`).

## Текущее состояние
- Реализовано: `GET /api/public/cities`, `GET /api/public/products`.
- Только `tenant_id=1`.
- Прайс для public API читается из `products` (legacy-цены).

## План (дальше)
### Шаг 1. Анализ БД
(см. секцию таблиц ниже)

### Шаг 2. DTO (контракт для сайта)
- CityDTO, CompanyDTO, ProductCardDTO, ProductPageDTO.

### Шаг 3. Public API Endpoints
- `GET /api/public/cities` (сделано)
- `GET /api/public/products` (сделано)
- `GET /api/public/products/{slug}` (не сделано)
- `GET /api/public/companies` (не сделано)
- `POST /api/public/leads` (не сделано)

### Шаг 4. Правила
- Только `tenant_id=1`.
- Только активные товары: `is_visible = 1` и `archived_at IS NULL`.
- Кэширование ответов (Cache-Control).

### Шаг 5. Архитектура
- Модуль: `app/Modules/PublicApi/*`.

### Шаг 6. Риски
- Привязка `city -> company` логическая (FK нет).
- `slug` может быть пустым в старых данных.
- Публичные цены пока берутся из `products`, а не из `product_company_prices`.

## Таблицы (кратко)
- `cities`, `companies`, `products`, `product_descriptions`, `product_media`,
  `product_categories`, `product_subcategories`, `product_brands`, `product_types`, `product_kinds`, `product_units`,
  `product_attribute_definitions`, `product_attribute_values`, `product_relations`.

## REALITY STATUS
- Реально реализовано: Public API модуль с cities/products.
- Легаси: цены берутся из `products`.
- Не сделано: companies, product-by-slug, leads, согласование pricing с `product_company_prices`.
