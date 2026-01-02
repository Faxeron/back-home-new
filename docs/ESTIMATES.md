# Estimates (rules + structure)

Source of truth
- `estimate_items` is the authoritative list of estimate rows.
- `estimates.data` is legacy/cache only (temporary during migration).

Estimate header fields
- client_name (required in UI), client_phone, site_address.
- client_id stores the linked counterparty id (lookup by phone on create; create if missing).
- New counterparties are created as type `individual`; legal entity is only set during contract flow.
- These fields are a snapshot: editing an estimate does not update the counterparty record.

Grouping
- Grouping is automatic by product type.
- `estimate_groups.product_type_id` maps product types to estimate groups.

Template application rules
- Template is resolved by SKU via `estimate_template_septiks.data` (list of SKUs).
- Template items come from `estimate_template_materials.data` (list of {scu, count}).
- Items are merged by `product_id`.
- If a matching row exists and `unit_price` differs, overwrite with current price list value.
- If no matching row, create it from price list.

Auto-qty sources
- `estimate_item_sources` stores auto-generated contributions per root product.
- `estimate_items.qty_auto` is the sum of `estimate_item_sources.qty_total` per product.
- Manual edits are tracked via `estimate_items.qty_manual`.
- Total qty = `qty_auto + qty_manual`.

Prices
- Default unit price: `products.price_sale` fallback to `products.price`.
- Prices may be overwritten by template application; final adjustments happen after quantities are settled.
