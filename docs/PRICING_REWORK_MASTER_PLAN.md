# Pricing Core Rework — Master Plan

## Цель
Перенести операционные цены (price/price_sale/price_delivery/montaj/montaj_sebest) в отдельную таблицу `product_company_prices` с привязкой к (tenant_id, company_id, product_id) и переподключить весь ERP-код без регрессий.

## Принципы
- Документы не должны «плыть»: сметы/договоры используют слепок (snapshot).
- Чтение/запись операционных цен — только через PriceResolver/PriceWriter.
- Миграция в 3 фазы: dual-read > dual-write > cutover.

## Фаза 1 — Dual Read
### Задачи
1) Таблица `product_company_prices` + индексы.
2) `PriceResolverService` (dual read) + `PriceWriterService`.
3) Feature flag `pricing.mode`.
4) Backfill-команда для company_id=1,2 (tenant=1).

### Затрагиваемые файлы
- `database/migrations_new/*create_product_company_prices*.php`
- `app/Domain/Catalog/Models/ProductCompanyPrice.php`
- `app/Services/Pricing/PriceResolverService.php`
- `app/Services/Pricing/PriceWriterService.php`
- `app/Console/Commands/BackfillProductCompanyPrices.php`
- `app/Console/Kernel.php`
- `config/pricing.php` (или `config/app.php` с секцией pricing)

### Проверки
- Запуск backfill на tenant=1, company=1/2 (dry-run или ограниченный прогон).
- Проверка выборки цен через PriceResolver (fallback на products).

## Фаза 2 — Dual Write
### Задачи
- Все места записи операционных цен: писать в `product_company_prices` через PriceWriter.
- Временно синхронизировать старые поля в `products` (до cutover).
- Зафиксировать в Agent.md оставшиеся legacy-зависимости.

### Основные места записи
- `PricebookService` (импорт/обновление прайса).
- `ProductController` (PATCH товара и inline редактирование цен).

### Проверки
- Импорт прайса обновляет новую таблицу.
- UI редактирование цены обновляет обе таблицы.

## Фаза 3 — Cutover
### Задачи
- Перевести ключевые модули на чтение ТОЛЬКО из `product_company_prices`.
- Переключить `pricing.mode = company_table_only`.
- Убрать fallback из PriceResolver.
- После стабилизации удалить операционные поля из `products`.

### Зоны переподключения (Chunks)
1) UI товаров / прайсы (read)
2) Импорт/экспорт прайса
3) Сметы/КП/договоры — слепок цен
4) Зарплаты/маржинальность
5) Отчеты

## Риски
- Пустые цены при отсутствии строки в `product_company_prices`.
- Неполный backfill (особенно для `is_global` товаров).
- Сценарии, которые читают цену напрямую из `products` (скрытые зависимости).

## Definition of Done
- Все операции чтения/записи операционных цен проходят через сервис.
- Документы (сметы/договоры) стабильны, цены не «плывут».
- Смоук-сценарии пройдены.
- Чистые коммиты + push после каждого чанка.
