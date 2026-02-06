# Стандарт таблиц CRM



Назначение

- Зафиксировать единый стандарт для UI, конфигурации, поведения и справочников таблиц CRM.

- Убрать разрозненные реализации на страницах.



Стандартные компоненты (проверенные пути)

- UI-обертка: `resources/ts/components/BaseDataTable.vue`

- Конфигурация колонок: `resources/ts/config/tables/*.ts`

- Поведение и lazy-логика: `resources/ts/composables/useTableLazy.ts`

- Справочники: `resources/ts/stores/dictionaries.ts`



Правила

1) Новые таблицы делаем через `BaseDataTable.vue` или `components/tables/settings/DictionaryTable.vue`. PrimeVue DataTable напрямую в страницах не используем.
2) Фильтры только с `filterDisplay="row"` и только в `#filter` слоте колонок.

3) Фильтры меняют реактивный `filters`, DataTable фильтрует сам (без ручных submit/watch).

4) Справочники берутся только из Pinia `dictionaries.ts`. Не собираем значения из строк таблицы.

5) Колонки, body templates и форматтеры описываем в `resources/ts/config/tables/*.ts`.

6) Пагинация, сортировка, filter-to-API, debounce/reset/reload - в `useTableLazy.ts`.
7) Иконки выбора matchMode (воронки) скрыты; matchMode задается в коде через объект filters.
8) Используем `Select` и `Popover` (PrimeVue v4); `Dropdown` и `OverlayPanel` не применять.
9) Включаем `stripedRows` для всех таблиц.
10) Фильтрация и сортировка только на сервере; правила и параметры фиксируются в `docs/filterRules.txt`.
11) Высота строки фильтров компактная: задается глобально в `resources/ts/@layouts/styles/_global.scss` (без локальных overrides).
12) "Всего: N" в шапке таблицы выводим через `TableTotalLabel` (`resources/ts/components/common/TableTotalLabel.vue`).


Как добавлять новую таблицу

- Создать или расширить конфиг в `resources/ts/config/tables/`.

- В странице использовать `BaseDataTable.vue` и подключить `useTableLazy.ts`.

- Нужные справочники загрузить один раз через Pinia и использовать map id -> name.



Исключения

- Любое отклонение от стандарта обсуждается и фиксируется в этом документе.



TODO: Визуальные стандарты таблиц

- Определить и закрепить: отступы ячеек (top/right/bottom/left), межстрочный интервал, высоту строки.

- Задать жесткие ширины для типовых колонок (ID, суммы, даты, статусы).

- Единые цвета: header, hover, selected, zebra, warning/negative.

- Централизовать это через класс таблицы + CSS-переменные (один источник правок).

## REALITY STATUS
- Реально реализовано: `BaseDataTable.vue`, `useTableLazy.ts`, конфиги колонок в `resources/ts/config/tables`.
- Легаси: отдельные таблицы могут обходить стандарт.
- Не сделано: единая визуальная система таблиц (см. TODO).
