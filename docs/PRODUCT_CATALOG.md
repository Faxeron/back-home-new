# Product catalog (pages + data tables)

Pages (route wrappers)
- `/products` — список товаров (таблица + карточки).
- `/products/price` — таблица цен.
- `/products/{id}` — карточка товара (табами).
- `/products/categories`, `/products/subcategories`, `/products/brands` — справочники каталога.
- `/products/templates` — шаблоны/связи (UI раздел каталога).
- `/products/estimates` — связанный каталог/сметные настройки (UI раздел каталога).

UI tables
- Products table: id, name, scu, kind, category, brand, is_visible, updated_at, action to open card.
- Price table: id, scu, flags (is_visible/is_top/is_new), category/subcategory/brand, name,
  price, price_sale, price_vendor, price_vendor_min, price_zakup, price_delivery, montaj, montaj_sebest.

Behavior
- Inline edits save via PATCH `/api/products/{id}`.
- Операционные цены в API возвращаются из `product_company_prices` (через `PriceResolverService`).
- Вендор/закуп остаются в `products`.

Frontend entry points (module-first)
- `resources/ts/modules/products/pages/products/index.vue`
- `resources/ts/modules/products/pages/products/price.vue`
- `resources/ts/modules/products/pages/products/[id].vue`
- `resources/ts/modules/products/components/ProductsTable.vue`
- `resources/ts/modules/products/components/ProductsPriceTable.vue`
- `resources/ts/modules/products/composables/useProductFilters.ts`
- `resources/ts/modules/products/composables/useProductLookups.ts`
- `resources/ts/modules/products/api/products.api.ts`
- `resources/ts/modules/products/types/products.types.ts`

Catalog schema additions
- `product_units`, `product_descriptions`, `product_attribute_definitions`, `product_attribute_values`,
  `product_media`, `product_relations`, `product_kinds`.
- `product_company_prices` — операционные цены по (tenant_id, company_id, product_id).

Work kinds
- `installation_linked`: монтажная работа связана через `product_relations` (INSTALLATION_WORK).
- `work_standalone`: самостоятельная работа, цена закупа редактируется напрямую.

## REALITY STATUS
- Реально реализовано: модульные страницы каталога, pricebook import/export, `product_company_prices` + PriceResolver.
- Легаси: поля операционных цен в `products` сохраняются и синхронизируются в `product_company_prices`.
- Не сделано: полный отказ от чтения legacy price-полей в сервисах вне каталога.
