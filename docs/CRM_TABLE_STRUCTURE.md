# Структура таблиц CRM

Назначение
- Зафиксировать файловую структуру и роли компонентов для таблиц CRM.
- Снизить связность между страницей, UI таблицы, фильтрами и загрузкой данных.

Слои и ответственность
- Страница-оркестратор (`resources/ts/pages/...`): загружает справочники, связывает фильтры и данные, прокидывает props, обрабатывает события.
- UI-таблица (`resources/ts/components/tables/...`): рендерит таблицу и фильтры, эмитит события, не знает про API и бизнес-логику.
- Фильтры (composable): один источник истины для filters/sort, сериализация параметров для API.
- Загрузка данных (`resources/ts/composables/useTableLazy.ts`): useTableLazy/useTableInfinite, lazy и infinite scroll, reset/reload.
- Конфиг таблицы (`resources/ts/config/tables/*.ts`): columns, заголовки, форматтеры, ширины, sort.
- Форматтеры (`resources/ts/utils/formatters/*.ts`): чистые функции для дат, сумм, статусов.
- Справочники (`resources/ts/stores/dictionaries.ts`): источники данных dropdown/select и map id -> name.

Файловая схема (пример transactions)
- `resources/ts/pages/finance/transactions.vue`
- `resources/ts/components/tables/transactions/TransactionsTable.vue`
- `resources/ts/composables/useTransactionFilters.ts`
- `resources/ts/composables/useTableLazy.ts`
- `resources/ts/config/tables/transactions.ts`
- `resources/ts/utils/formatters/finance.ts`
- `resources/ts/stores/dictionaries.ts`

Справочники (settings)
- UI: `resources/ts/components/tables/settings/DictionaryTable.vue` + обертки по сущностям.
- Фильтры: `resources/ts/composables/useDictionaryFilters.ts` (маппинг на `q` и select‑поля).
- Конфиги: `resources/ts/config/tables/*.ts` для каждой сущности.

Инварианты
- Таблицы через `BaseDataTable.vue` или `components/tables/settings/DictionaryTable.vue`. PrimeVue DataTable напрямую в страницах не использовать.
- Фильтрация и сортировка только на сервере; правила и параметры описаны в `docs/filterRules.txt`.
- Infinite scroll: `per_page` фиксирован (стандарт 200), пагинация скрыта.
- `filterDisplay="row"`, matchMode задается только через объект filters (иконки выбора скрыты).

Правило debounce (текущее)
- Debounce только для текстовых полей: id, contract_or_counterparty, notes, related (и аналогичные).
- Мгновенно: select/enum, boolean, диапазоны дат, суммы/диапазоны сумм.

Шаблоны (docs/templates)
- `docs/templates/table-page.template.vue`
- `docs/templates/TableComponent.template.vue`
- `docs/templates/useEntityFilters.template.ts`
- `docs/templates/table-config.template.ts`
- `docs/templates/formatters.template.ts`
- `docs/templates/README.md`

Как добавлять новую таблицу (кратко)
1) Скопировать шаблоны из `docs/templates`.
2) Создать конфиг колонок в `resources/ts/config/tables`.
3) Создать composable фильтров и подключить `useTableLazy.ts`.
4) В странице оставить только оркестрацию и загрузку справочников.

TODO
- URL-sync фильтров/сортировки (deep-link).
- Единый buildQuery/контракт фильтров для всех таблиц.
- Сброс tenant-scoped справочников при смене tenant/company.
