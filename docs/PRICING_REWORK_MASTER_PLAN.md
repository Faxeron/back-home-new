# Pricing Core Rework — Master Plan

## 1) Текущая архитектура цен (as-built)
- Операционные цены (`price`, `price_sale`, `price_delivery`, `montaj`, `montaj_sebest`) читаются из `product_company_prices` через `PriceResolverService`.
- `ProductController` и `PricebookService` пишут операционные цены в `products`, затем синхронизируют `product_company_prices`.
- `ProductResource` возвращает price-поля из `product_company_prices`.
- `EstimateTemplateService` берет цену только через `PriceResolverService` (нет fallback).
- Анализ договора: `ContractController` использует `product.price_zakup` + delivery из `product_company_prices`.
- Public API читает цены из `products` (legacy).
- Feature flag отсутствует, dual-read не реализован.

## 2) Новая архитектура
- Источник операционных цен: `product_company_prices` по ключу `(tenant_id, company_id, product_id)`.
- В `products` остаются только закупочные/вендорные поля:
  - `price_vendor`, `price_vendor_min`, `price_zakup`.
- Документы используют слепок цен и не пересчитываются «на лету».

## 3) План миграции

### Этап 1 — Dual Read
Цель: безопасно читать цены из новой таблицы с fallback на legacy.
- Добавить feature flag `pricing.mode` (например: `dual_read`, `company_table_only`).
- В `PriceResolverService` реализовать fallback на `products` при отсутствии строки `product_company_prices`.
- Логировать отсутствующие цены (метрика или отчёт).
- Обновить docs и smoke-сценарии.

### Этап 2 — Dual Write
Цель: гарантированно писать в новую таблицу.
- Все записи операционных цен — только через `PriceWriterService`.
- Временно синхронизировать legacy поля в `products` (до cutover).
- Проверить точки записи:
  - `PricebookService` импорт.
  - `ProductController` PATCH.
  - Любые batch-операции/консольные команды.

### Этап 3 — Cutover
Цель: исключить legacy-цены из чтения.
- Перевести все чтения на `product_company_prices`.
- Убрать fallback в `PriceResolverService`.
- Обновить Public API на `product_company_prices`.
- Удалить/заморозить legacy price-поля в `products` (после периода стабилизации).

## 4) Затронутые модули
- Товары (ProductController, ProductResource, UI).
- Прайс (Pricebook import/export).
- Сметы и шаблоны (EstimateTemplateService).
- Договоры и анализ маржи (ContractController, contract_items).
- Зарплаты/маржинальность (через contract_items и finance_allocations).
- Public API (ProductCard/ProductPage transformers).

## 5) Риски
- Пустые цены при отсутствии строки в `product_company_prices`.
- Несогласованность public/internal pricing.
- Скрытые зависимости на `products.price*`.
- Ошибки миграции при неполном backfill.

## 6) Definition of Done
- Все операционные цены читаются/пишутся через `PriceResolver/PriceWriter`.
- Все документы используют слепок цен (estimate_items/contract_items).
- `product_company_prices` покрывает 100% активных товаров (нет пропусков).
- Public API использует новый источник цен.
- Legacy поля `products.price*` не используются или удалены.
- Smoke и регрессионные сценарии пройдены.

## REALITY STATUS
- Реально реализовано: `product_company_prices`, PriceResolver/PriceWriter, синхронизация при импорте и ручном редактировании.
- Легаси: public API и часть логики читают `products`.
- Не сделано: dual-read fallback, feature flag, полный cutover.
