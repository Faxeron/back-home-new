# PUBLIC API CONTRACT

## Общие правила
- Base URL: `/api/public`.
- Только `tenant_id=1` (публичный сайт).
- Для product endpoints обязателен контекст: `company_id` или `city`.
- `city` — slug из `cities`, должен иметь `company_id`.
- `company_id` должен существовать в `companies` (tenant=1).
- Цены берутся только из `product_company_prices`.
- В публичный каталог **не попадают** товары без активной строки цены (`product_company_prices.is_active=1`) или без числовой цены (`price_sale` или `price` должны быть не NULL).
- Кэш: серверный Cache::remember с ключом, включающим `company_id`/`city`.
- Для отладки можно отключить серверный кэш: `?no_cache=1` (Cache-Control: no-store).

## Диагностика отсутствия товара в каталоге (цены/активность)
Если товара нет в списке `/api/public/products`, это означает одно из:
1) В `product_company_prices` нет активной строки для `tenant_id=1` + `company_id` + `product_id` (`is_active=1`).
2) Активная строка есть, но оба поля `price_sale` и `price` равны `NULL` (публичный каталог такие товары не показывает).
3) Товар скрыт: `products.is_visible=0` или `products.archived_at IS NOT NULL`.

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

## GET /api/public/catalog/tree
Дерево каталога для построения меню/фильтров.

Query:
- `company_id` или `city` (обязательно)

Response:
```json
{
  "data": {
    "categories": [
      {
        "id": 1,
        "slug": "septiki",
        "name": "Септики",
        "sort_order": 10,
        "children": [
          {
            "id": 11,
            "slug": "evrolos",
            "name": "Евролос",
            "sort_order": 10
          }
        ]
      }
    ],
    "brands": [
      { "id": 2, "slug": "evrolos", "name": "Евролос", "sort_order": 10 }
    ],
    "filters": [
      { "id": 10, "code": "people", "name": "Для людей", "unit": "чел", "value_type": "number", "sort_order": 10 }
    ]
  }
}
```

---

## GET /api/public/products
Список товаров (company-aware).

Query:
- `company_id` или `city` (обязательно)
- `page` (optional, default 1)
- `per_page` (optional, default 24, max 100)

Фильтры (optional):
- `category_id`
- `sub_category_id`
- `brand_id`
- `price_min`
- `price_max`
- `q` (поиск по `products.name`/`products.scu`)
- `attrs[ID]=value` (например `attrs[10]=5`)

Сортировка:
- по умолчанию: `ORDER BY products.sort_order ASC, products.id DESC`

Ответ:
- `data[*].price.price` это "зачеркнутая" цена (старая)
- `data[*].price.price_sale` это актуальная цена
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
      "sort_order": 120,
      "is_top": true,
      "is_new": false,
      "category": { "id": 3, "slug": "septiki", "name": "Септики" },
      "subcategory": { "id": 7, "slug": "evrolos", "name": "Евролос" },
      "brand": { "id": 2, "slug": "brand", "name": "Brand" },
      "price": {
        "price": 120000,
        "price_sale": 99000,
        "price_delivery": 3000,
        "montaj": 15000,
        "currency": "RUB"
      },
      "image": "/storage/.../1.jpg",
      "description_short": "Короткое описание...",
      "company_id": 1
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 24,
    "total": 120,
    "has_more": true
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
    "sort_order": 120,
    "is_top": true,
    "is_new": false,
    "category": { "id": 3, "slug": "septiki", "name": "Септики" },
    "subcategory": { "id": 7, "slug": "evrolos", "name": "Евролос" },
    "brand": { "id": 2, "slug": "brand", "name": "Brand" },
    "price": {
      "price": 120000,
      "price_sale": 99000,
      "price_delivery": 3000,
      "montaj": 15000,
      "currency": "RUB"
    },
    "description_short": "Короткое описание...",
    "description_long": "Длинное описание...",
    "media": [
      { "url": "/storage/.../1.jpg", "alt": "Фото", "is_main": true, "sort_order": 10, "type": "image" }
    ],
    "attributes": [
      { "id": 10, "code": "people", "name": "Для людей", "value": 5, "unit": "чел" }
    ],
    "related_products": [
      { "id": 202, "slug": "septik-xyz", "name": "Септик XYZ", "price": { "price": 100000, "price_sale": 95000, "price_delivery": null, "montaj": null, "currency": "RUB" }, "image": "/storage/.../2.jpg", "description_short": null, "sort_order": 1000, "is_top": false, "is_new": false, "category": null, "subcategory": null, "brand": null, "company_id": 1 }
    ],
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
- Реально реализовано: `cities`, `companies`, `catalog/tree`, `products` (list + slug), `leads`.
- Легаси: параметр `category` в `/products` (оставлен для совместимости, но рекомендуется `category_id`).
- Не сделано: отдельный endpoint для SEO-страниц категорий/брендов (пока строится на основе `catalog/tree` + `/products`).
