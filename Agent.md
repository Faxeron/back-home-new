# Agent Rules (Pricing Core Rework)

## Цель и приоритеты
- P0: переподключить операционные цены на отдельную таблицу `product_company_prices` без регрессий.
- Данные остаются консистентными для документов: сметы/договоры используют слепок (snapshot).
- Архитектура мульти-tenant, текущая работа — tenant_id=1.

## Workflow (обязательный)
1) Task — сформулировать подзадачу и границы изменений.
2) Plan — краткий план + список затрагиваемых файлов/таблиц.
3) Implement — изменения только в пределах плана.
4) Verify — тесты/проверки + smoke-сценарии.
5) Report — что сделано, какие файлы, какие команды, риски.

## Зоны изменений
Разрешено
- `app/` (Controllers/Services/Domain/Repositories/Resources/Requests)
- `database/migrations_new/*`
- `config/*` (только если нужно)
- `docs/*`
- `routes/*`

Запрещено
- `vendor/`, `node_modules/`, `public/`, `storage/` (кроме явных файлов, если требуется),
- любые .env/секреты,
- обход `FinanceService` для финансовых операций.

## Безопасность и стабильность
- Не коммитить секреты/ключи/конфиги окружения.
- Не выполнять destructive-команды (reset --hard и т.п.).
- Любая миграция должна быть идемпотентной и безопасной.
- Всегда используем connection `legacy_new` для доменных данных.
- Новые чтения/записи операционных цен — только через PriceResolver/PriceWriter.

## GIT DISCIPLINE (обязательно)
После **каждого чанка**:
1) `git status`
2) `git add -A`
3) `git commit -m "<осмысленное сообщение>"`
4) `git push`

## Definition of Done (для каждого чанка)
- Код/миграции готовы и соответствуют плану.
- Документация актуальна (если применимо).
- Выполнены проверки/тесты.
- Отчет предоставлен.
- Коммит + push выполнены.

## Новая модель цен (ценообразование)
- **В `products` остаются только закупочные/вендорские цены:**
  - `price_vendor`, `price_vendor_min`, `price_zakup`
- **Операционные цены в `product_company_prices`:**
  - `price`, `price_sale`, `price_delivery`, `montaj`, `montaj_sebest`
- Привязка цен: `(tenant_id, company_id, product_id)`
- Истории цен нет (только текущие значения).

## Текущие источники цен (legacy)
- `products`: `price`, `price_sale`, `price_delivery`, `montaj`, `montaj_sebest` + vendor/zakup.
- `PricebookService` импорт/экспорт напрямую пишет/читает поля `products`.
- `ProductController`/`ProductResource` отдают и обновляют цены напрямую в `products`.
- `EstimateTemplateService::resolvePrice()` берет `product.price_sale ?? product.price`.
- `EstimateItemController` при создании позиции использует `EstimateTemplateService`.
- `ContractController::analysis()` использует `product.price_zakup` и `product.price_delivery` для плановой себестоимости.

## Слепок сметы (snapshot)
- Источник истины по строкам сметы: `estimate_items`.
- В `estimate_items` хранится `price` и `total` — это **слепок** на момент создания/пересчета.
- `estimates.data` — legacy/кеш (используется как fallback в `ContractController::analysis()`).
- Документы не должны «плыть»: все пересчеты должны фиксировать цену в `estimate_items` и/или `contract_items`.

## Модули ERP для переподключения цен
1) UI товаров / прайсы (list/view)
2) Импорт/экспорт прайса (xlsx)
3) Сметы/КП/договоры — слепок цен
4) Зарплаты/мотивация/маржинальность
5) Отчеты

## Feature flag
- `pricing.mode = dual_read` (default)
- `pricing.mode = company_table_only` (после cutover)

## Проверки/тесты
- Минимум: `phpunit` (или `vendor/bin/phpunit`).
- Если тестов нет — ручные smoke-сценарии обязательны.

## Правила без регресса
- Цены в документах только из snapshot (не из products/прайса).
- В `products` после cutover не должно быть чтения операционных цен.
- Обязательны тесты/проверки + smoke сценарии из задачи.

## Smoke-сценарии
- Открыть список товаров — цены отображаются по company.
- Экспорт прайса — корректные цены.
- Импорт прайса — корректно обновляет цены.
- Создание сметы — цена подтягивается и фиксируется в `estimate_items`.
- Пересчет/сохранение сметы — не ломается.

## Legacy ??????????? (????? cutover)
- ???: ?????? ???????????? ??? ?????? ?? `product_company_prices`, fallback ??????.
