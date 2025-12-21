# API NORMALIZATION PLAN

- Canonical prefix: `/api/finance/*` (единый, без множественного числа).

## Шаги миграции без поломки фронта
1) В `routes/api.php` оставить реальные контроллеры только под `/api/finance/*`; для совместимости держать legacy prefix "finances" (Route::prefix('finances') -> redirect/alias к finance).
2) Добавить middleware-депрекейт: для обращений к legacy prefix "finances" выставлять header `Deprecation: true`, `Link: <.../api/finance/...>; rel="successor-version"` и логировать обращения.
3) Обновить фронт/SDK: переписать endpoints в `resources/ts/api/finance/*`, `useTableLazy` конфиги, Pinia dictionaries на новый префикс; оставить переадресацию на сервере на 1-2 релиза.
4) Тесты/контракты: добавить интеграционные тесты на оба префикса в переходный период + assert Deprecation header для legacy.
5) Через N релизов (зафиксировать дату) убрать алиасы legacy prefix "finances", оставить 410/redirect с описанием обновления.
