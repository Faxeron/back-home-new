# Прайс-лист XLSX (импорт/экспорт)

Этот формат - строгий. Импорт/экспорт работает **только** по заданным листам и колонкам (порядок фиксирован).
Импорт всегда применяется к **текущему tenant/company** пользователя.

## Как пользоваться (UI/API)
- UI: Прайс → **Шаблон / Экспорт / Импорт**.
- API:
  - `GET /api/products/pricebook/template` - скачать пустой шаблон с колонками.
  - `GET /api/products/pricebook/export` - экспорт текущего прайса.
  - `POST /api/products/pricebook/import` - импорт XLSX (multipart `file`).
- Прямая ссылка на шаблон: `/api/products/pricebook/template`.
- Файлы импортов сохраняются в `storage/app/pricebooks/imports`.

## Листы и колонки

### 1) Products
Колонки (строго в этом порядке):
```
scu, name, product_type_id, product_kind_id, unit_id, category_id, subcategory_id, brand_id,
is_visible, is_top, is_new,
price, price_sale, price_vendor, price_vendor_min, price_zakup, price_delivery, montaj, montaj_sebest,
installation_work_scu, related_scu, action
```
Правила:
- **scu** — уникальный ключ.
- `product_kind_id` обязателен (пустых быть не должно).
- `is_visible/is_top/is_new` — 0/1.
- Числа: допускаются пробелы и запятая как разделитель (например `12,5`).
- `installation_work_scu` — 1 SKU работы (связь 1:1).
- `related_scu` — список SKU через запятую/точку с запятой.
- `action` (обязательное):
  - `CREATE` — создать новый товар (ошибка, если уже существует),
  - `UPDATE` — обновить существующий товар (ошибка, если не найден),
  - `DELETE` или `ARCHIVE` — архивировать товар (archived_at, is_visible=0).

### 2) Descriptions
Колонки:
```
scu, name, description_short, description_long, dignities, constructive, avito1, avito2
```
Правила:
- Для **каждого товара** из Products должна быть строка описания.
- Поля описаний можно оставлять пустыми, но строка для SKU обязательна.

### 3) Attributes
Колонки:
```
scu, attribute_name, value_string, value_number
```
Правила:
- `attribute_name` должен существовать в `product_attribute_definitions` **или** будет создан автоматически
  для указанного `product_kind_id` (value_type определяется по заполненному столбцу `value_number`).
- Если `value_type = number` — заполняем `value_number`.
- Если `value_type = string` — заполняем `value_string`.

### 4) Media
Колонки:
```
scu, type, path, sort_order
```
Правила:
- `type`: `image` или `video`.
- `path`: путь до файла (см. раздел про медиа).
- `sort_order`: число.

### 5) Lookups (справочно)
Экспортируется для удобства — это справочник ID.
Импорт не использует этот лист.

## Медиа (path)
- Все файлы складываем в папку **media** с внутренней структурой по товарам.
- В Excel указываем **путь к файлу** (например `media/products/СКУ/фото1.jpg`).

## Что делает импорт
- **Создает/обновляет** товары по `scu`.
- **Архивирует** товары по `action=DELETE/ARCHIVE` (без физического удаления).
- **Полностью синхронизирует** описания, атрибуты и медиа.
- Связи `installation_work_scu` и `related_scu` пересоздаются заново.
- `montaj_sebest` синхронизирует `price_zakup` у связанной работы (INSTALLATION_WORK).

## Ошибки импорта
Импорт **останавливается**, если:
- нарушен порядок/набор колонок,
- нет обязательных полей,
- неверные ID справочников,
- неизвестные SKU в связях,
- нет строки описания для товара.

Пока есть ошибки, данные не сохраняются.
