# Estimates (rules + structure)

Source of truth
- `estimate_items` — единственный источник строк сметы (qty/price/total).
- `estimates.data` — legacy/кеш, текущий код его не обновляет.

Estimate header fields
- `client_name`, `client_phone`, `site_address`.
- `client_id` хранит контрагента; при создании (не draft) контрагент ищется по телефону и создается при отсутствии.
- Эти поля — слепок: обновление сметы не меняет карточку контрагента.

Grouping
- Группировка по product_type через `estimate_groups`.
- Группа создается автоматически при первом использовании типа.

Template application rules
- Шаблон ищется по SKU в `estimate_template_septiks.data`.
- Материалы берутся из `estimate_template_materials.data` (массив `{scu, count}`).
- Авто-количества считаются в `estimate_item_sources`.
- При пересчете:
  - qty_auto пересчитывается из `estimate_item_sources`.
  - если цена в `estimate_items.price` уже задана, она не перезаписывается.

Prices
- Базовая цена: `PriceResolverService` (читает `product_company_prices`), `price_sale` -> `price`.
- Fallback на `products` отсутствует; при отсутствии строки в `product_company_prices` будет исключение.
- Итоговая цена фиксируется в `estimate_items` и не «плавает».

## REALITY STATUS
- Реально реализовано: auto-qty через `estimate_item_sources`, слепок цен в `estimate_items`.
- Легаси: `estimates.data` может содержать старые слепки и используется только в анализе договора.
- Не сделано: единый механизм обновления цены по шаблону при повторном применении (сейчас price не перезаписывается).
