# Product catalog (pages + data tables)

Pages
- /products: product list with table view + card view.
- /products/price: price table with inline edits.
- /products/:id: product card (tabs for main data, prices, descriptions, attributes, media, relations).

UI tables
- Products table columns: id, name, scu, kind, category, brand, is_visible, updated_at, open card action.
- Price table columns: id, scu, flags (is_visible/is_top/is_new), category, subcategory, brand, name, price, price_sale, price_vendor, price_vendor_min, price_zakup, price_delivery, montaj, montaj_sebest, open card action.

Behavior
- Inline edits save on blur via PATCH /api/products/{id}.
- Flags save immediately on toggle.
- Card view uses auto-load on scroll (IntersectionObserver).
- Default sort: sort_order ASC, then scu ASC.
- Filters: search, brand_id, category_id, sub_category_id.

Frontend entry points
- resources/ts/pages/products/index.vue
- resources/ts/pages/products/price.vue
- resources/ts/pages/products/[id].vue
- resources/ts/components/tables/products/ProductsTable.vue
- resources/ts/components/tables/products/ProductsPriceTable.vue
- resources/ts/composables/useProductFilters.ts
- resources/ts/composables/useProductLookups.ts
- resources/ts/api/products.ts
- resources/ts/types/products.ts

Catalog schema additions
- product_units: code, name, timestamps.
- product_descriptions: product_id (unique), description_short, description_long, dignities, constructive, avito1, avito2 + meta columns.
- product_attribute_definitions: name, value_type, product_type_id (nullable), product_kind_id (nullable) + meta columns.
- product_attribute_values: product_id, attribute_id, value_string, value_number + meta columns.
- product_media: product_id, type, url, sort_order + meta columns.
- product_relations: product_id, related_product_id, relation_type + meta columns.
- product_kinds: name + meta columns.
- products: unit_id, product_kind_id, work_kind (installation_linked/work_standalone), is_visible, is_top, is_new, price_vendor_min, price_delivery, sort_order.

Work kinds
- installation_linked: installation work linked via product_relations (INSTALLATION_WORK); price_zakup is synced from parent product montaj_sebest.
- work_standalone: standalone work; price_zakup is edited directly.
