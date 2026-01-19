# UI standards

Purpose
- Keep a single visual language across pages.
- Split responsibilities: layout/forms on Vuexy (Vuetify), tables on PrimeVue.

Baseline
- Layout/blocks/forms: Vuexy/Vuetify (`VCard`, `VCardText`, `VRow`, `VCol`, `VTextField`, `VAutocomplete`, `VBtn`, `VDivider`).
- Tables: PrimeVue DataTable (`DataTable`, `Column`) via `BaseDataTable.vue` or `components/tables/settings/DictionaryTable.vue`.
- Do not replace data tables with Vuetify tables.

Structure
- Follow module layering. See `docs/STRUCTURE_GUIDE.md`.

Module map (frontend)
- finance: transactions/receipts/spendings + finance dictionaries
- settings: common dictionaries (companies, cities, districts, contract statuses, sale types)
- production: contracts, measurements, installations
- estimates
- products

Block styling
- Use `VCard variant="outlined"` for form blocks.
- If a title must be "cut into" the frame, use a small overlay label tied to the card border (see estimate editor Vuexy variant).
- Keep spacing with Vuexy utility classes (`d-flex`, `gap-*`, `text-subtitle-*`) instead of custom CSS where possible.

Phone input (global)
- Use `AppPhoneField` for all phone inputs (Vuexy forms).
  - File: `resources/ts/@core/components/app-form-elements/AppPhoneField.vue`
  - Format: `+7 999 999 99 99`
  - Digits-only, trimmed to 11 digits (`+7` + 10 digits).
  - Max length: 16 characters (including spaces and +).
- Do not use plain `VTextField`/`AppTextField` for phone numbers.
