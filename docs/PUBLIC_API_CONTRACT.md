# PUBLIC API CONTRACT

## Общие правила
- Base URL: `/api/public`.
- Только `tenant_id=1` (публичный сайт).
- Для product endpoints обязателен контекст: `company_id` или `city`.
- `city` — slug из `cities`, должен иметь `company_id`.
- `company_id` должен существовать в `companies` (tenant=1).
- Цены берутся только из `product_company_prices`.
- Если для компании нет цены, все price fields = null (включая `price_sale`).
- Кэш: серверный Cache::remember с ключом, включающим `company_id`/`city`.
- Для отладки можно отключить серверный кэш: `?no_cache=1` (Cache-Control: no-store).

## Диагностика NULL-цен (обязательная проверка при интеграции)
Если на витрине появляется "Цена по запросу", это означает ровно одно: API вернул `null` в `price`/`price_sale` (фронт это не "придумывает").

Есть 2 разные причины на стороне ERP:
1) В `product_company_prices` нет строки для нужного `product_id` + `company_id` (или строка есть, но `is_active=0`).
2) Строка есть, но оба поля `price` и `price_sale` равны `NULL` (например, сделали backfill структуры без фактических значений).

Чек-лист (tenant=1, companies=1,2):
```bash
php artisan pricing:report-missing-company-prices --tenant=1 --companies=1,2 --limit=20
php artisan pricing:report-null-company-prices --tenant=1 --companies=1,2 --limit=20
```

Если `missing ... > 0`:
- нужно создать строки в `product_company_prices` для этих товаров (обычно через импорт прайса в ERP).

Если `... price and price_sale are NULL > 0`:
- значит цены не были загружены/перенесены, и сайт корректно показывает "Цена по запросу".

## Ошибки
- `400` — отсутствует `company_id`/`city` или некорректные значения.
- `404` — товар не найден (product by slug).
- `422` — валидация leads.

---

## GET /api/public/cities
Справочник городов.

Query:
- `q` (optional) — поиск по name/slug.
- `company_id` (optional) — фильтр по компании.

Response:
```json
{
  "data": [
    {
      "slug": "surgut",
      "name": "Сургут",
      "company_id": 1
    }
  ]
}
```

---

## GET /api/public/companies
Справочник компаний (tenant=1).

Query:
- `company_id` (optional)
- `city` (optional)

Response:
```json
{
  "data": [
    {
      "id": 1,
      "name": "ООО Ромашка",
      "phone": "+7 999 111 22 33",
      "address": "Сургут, ул. ...",
      "email": "info@example.ru"
    }
  ]
}
```

---

## GET /api/public/products
Список товаров (company-aware).

Query:
- `company_id` или `city` (обязательно)
- `category` (optional) — id или name
- `page` (optional, default 1)
- `per_page` (optional, default 24, max 100)

Response:
```json
{
  "data": [
    {
      "id": 101,
      "slug": "septik-abc",
      "name": "Септик ABC",
      "price": 120000,
      "price_sale": 99000,
      "price_delivery": 3000,
      "montaj": 15000,
      "currency": "RUB",
      "images": ["/storage/.../1.jpg"],
      "category": {"id": 3, "name": "Септики"},
      "brand": {"id": 2, "name": "Brand"},
      "city_available": ["surgut"],
      "company_id": 1
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 24,
    "total": 120,
    "last_page": 5
  }
}
```

---

## GET /api/public/products/{slug}
Карточка товара (company-aware).

Query:
- `company_id` или `city` (обязательно)

Response:
```json
{
  "data": {
    "id": 101,
    "slug": "septik-abc",
    "name": "Септик ABC",
    "description": "...",
    "specs": [{"name": "Объем", "value": "2м3"}],
    "images": ["/storage/.../1.jpg"],
    "price": 120000,
    "price_sale": 99000,
    "price_delivery": 3000,
    "montaj": 15000,
    "currency": "RUB",
    "brand": {"id": 2, "name": "Brand"},
    "category": {"id": 3, "name": "Септики"},
    "faq": [],
    "seo": null,
    "company_id": 1
  }
}
```

---

## POST /api/public/leads
Создание лида (tenant=1).

Query:
- `company_id` или `city` (обязательно)

Body (JSON):
- `name` (required)
- `phone` (required)
- `email` (optional)
- `message` (optional)
- `product_id` (optional)
- `page_url` (optional)
- `source` (optional, default `public_api`)
- `utm_source`, `utm_medium`, `utm_campaign`, `utm_content`, `utm_term` (optional)
- любые дополнительные поля сохраняются в `payload`

Response:
```json
{
  "data": {
    "id": 987,
    "status": "created"
  }
}
```

## REALITY STATUS
- Реально реализовано: endpoints и поля соответствуют текущему public API.
- Легаси: нет.
- Не сделано: публичная аутентификация/ограничения доступа (не требуется).
