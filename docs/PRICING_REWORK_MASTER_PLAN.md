# Pricing Core Rework — Master Plan

## 1) Текущая архитектура цен (as-built)
- Операционные цены (`price`, `price_sale`, `price_delivery`, `montaj`, `montaj_sebest`) читаются из `product_company_prices` через `PriceResolverService`.
- В `products` по-прежнему хранятся legacy-операционные цены и закупочные поля (`price_vendor`, `price_vendor_min`, `price_zakup`).
- Импорт прайса и ручное редактирование товаров пишут в `products`, затем синхронизируют `product_company_prices`.
- Public API читает цены из `products` (legacy).
- Feature flag `pricing.mode` отсутствует.

## 2) Новая архитектура
- Источник операционных цен: `product_company_prices` по ключу `(tenant_id, company_id, product_id)`.
- В `products` остаются только закупочные/вендорные поля.
- Документы используют слепок цен (estimate_items, contract_items), цены не «плавают».

## 3) План миграции
### Этап 1 — Dual Read
- Ввести fallback: читать из `product_company_prices`, при отсутствии — из `products`.
- Контролировать полноту через команду `pricing:report-missing-company-prices`.

### Этап 2 — Dual Write
- Все записи операционных цен идут в `product_company_prices` через `PriceWriterService`.
- Временно синхронизировать legacy поля в `products`.

### Этап 3 — Cutover
- Перевести все чтения на `product_company_prices`.
- Удалить fallback.
- После стабилизации убрать legacy поля из `products`.

## 4) Затронутые модули
- Товары (ProductController, ProductResource, UI).
- Прайс (Pricebook import/export).
- Сметы/договоры (EstimateTemplateService, ContractController analysis).
- Зарплаты/маржинальность (через contract_items и finance_allocations).
- Public API (ProductCard/ProductPage transformers).

## 5) Риски
- Пустые цены при отсутствии строки в `product_company_prices`.
- Несогласованность public API и внутреннего pricing.
- Скрытые зависимости на поля `products.price*`.

## 6) Definition of Done
- Все чтения/записи операционных цен проходят через `PriceResolver/PriceWriter`.
- Документы используют слепок, без динамических цен.
- Backfill завершен, нет пропусков по `product_company_prices`.
- Legacy поля `products.price*` удалены или не используются.

## REALITY STATUS
- Реально реализовано: `product_company_prices`, PriceResolver/PriceWriter, синхронизация при импорте и ручном редактировании.
- Легаси: public API и часть логики все еще читают `products`.
- Не сделано: dual-read fallback, feature flag, полный cutover.
