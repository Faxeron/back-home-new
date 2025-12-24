# CRM Table Templates

Placeholders
- __DOMAIN__      lower-case domain (e.g. finance)
- __Domain__      PascalCase domain (e.g. Finance)
- __ENTITY__      UPPER_SNAKE (e.g. TRANSACTION)
- __Entity__      PascalCase (e.g. Transaction)
- __entity__      lower-case (e.g. transaction)

Files
- table-page.template.vue
- TableComponent.template.vue
- useEntityFilters.template.ts
- table-config.template.ts
- formatters.template.ts

Usage
1) Copy templates into the target folders from CRM_TABLE_STRUCTURE.md.
2) Replace placeholders with your real names.
3) Remove unused blocks (filters, columns, dictionaries).
