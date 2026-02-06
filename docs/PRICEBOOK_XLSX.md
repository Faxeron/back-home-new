# Прайс-лист XLSX (импорт/экспорт)

Формат строгий: листы и колонки должны совпадать с шаблоном. Импорт применяется к текущим tenant/company пользователя.

## Как пользоваться (UI/API)
- UI: Прайс > Шаблон / Экспорт / Импорт.
- API:
  - GET `/api/products/pricebook/template`
  - GET `/api/products/pricebook/export`
  - POST `/api/products/pricebook/import` (multipart `file`)
- Импорты сохраняются в `storage/app/pricebooks/imports`.

## Листы и колонки

### 1) Products
Колонки (строго в этом порядке, как в шаблоне):
```
action, scu, name, product_type_id, product_kind_id, unit_id, category_id, subcategory_id, brand_id,
is_visible, is_top, is_new,
price, price_sale, price_vendor, price_vendor_min, price_zakup, price_delivery, montaj, montaj_sebest,
related_scu,
work_scu, work_name, work_product_type_id, work_category_id,
work_price, work_price_sale, work_price_vendor, work_price_vendor_min, work_price_zakup
```
Правила:
- `action` обязательное: CREATE/UPDATE/DELETE/ARCHIVE.
- `scu` уникален.
- `is_visible/is_top/is_new` — 0/1.
- Числа: допускаются пробелы и запятая как разделитель.
- `related_scu` — список SKU через запятую/точку с запятой.
- `work_*` описывает связанную монтажную работу (INSTALLATION_WORK).

### 2) Descriptions
```
scu, name, description_short, description_long, dignities, constructive, avito1, avito2
```
- Для каждого SKU из Products должна быть строка описания.

### 3) Attributes
```
scu, attribute_name, value_string, value_number
```
- Если определения нет, оно создается автоматически для `product_kind_id`.

### 4) Media
```
scu, type, path, sort_order
```
- `type`: image | video.

### 5) Lookups (справочно)
Экспортируется для удобства, импорт не использует этот лист.

## Медиа (path)
- Файлы должны быть доступны по пути, указанному в Excel.
- В проекте принято хранить в `storage/app/public/...` и раздавать через `/storage/...`.

## Что делает импорт
- Создает/обновляет товары по `scu`.
- Архивирует по `action=DELETE/ARCHIVE` (archived_at, is_visible=0).
- Полностью синхронизирует описания, атрибуты и медиа.
- Связи `related_scu` и `work_scu` пересоздаются.
- `montaj_sebest` синхронизирует `price_zakup` у связанной работы.
- Операционные цены пишутся в `products`, затем синхронизируются в `product_company_prices`.

## Что делает экспорт
- Операционные цены (`price`, `price_sale`, `price_delivery`, `montaj`, `montaj_sebest`) берутся из `product_company_prices`.
- Вендор/закуп (`price_vendor*`, `price_zakup`) берутся из `products`.

## REALITY STATUS
- Реально реализовано: строгая схема листов + валидации, sync в `product_company_prices`.
- Легаси: операционные цены все еще сохраняются в `products`.
- Не сделано: отдельный import/export только для `product_company_prices` без legacy полей.
