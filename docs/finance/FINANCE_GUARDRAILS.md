# FINANCE GUARDRAILS (P0 hardening)
See also: `docs/finance/DELETION_AND_PAYROLL_RULES.md`

Статус: обязательный. Применяется ко всем изменениям Finance / Transactions / Cashboxes. Любое расхождение с документом считается ошибкой, даже если <сейчас работает>.

## 1. Назначение
Фиксируем неизменяемые правила финансового контура. Документ обязателен для всех разработчиков, code review и любых изменений в финансах.

## 2. Источник правды по балансу
- Единственный источник правды: `SUM(transactions.sum * sign(transaction_type))` по завершённым транзакциям.
- Расчёт только внутри DB-транзакции по актуальному состоянию `transactions`.
- Запрещено: хранить balance в `cashboxes`, пересчитывать баланс фоном, синхронизировать баланс через listeners/jobs.

## 3. Единственный писатель (Single Writer Rule)
- Все финансовые эффекты (transactions, cashbox_history, completion) создаются **только** через `app/Services/Finance/FinanceService.php`.
- Запрещено: писать баланс/историю из listeners, вызывать `CashboxBalanceService` напрямую, менять финсостояние из контроллеров/репозиториев/jobs. Любой обход удаляется, а не <чинится>.

## 4. Конкурентный доступ и гонки (P0)
- Проблема: параллельные запросы могут одновременно пройти проверку баланса и увезти кассу в минус.
- Решение: все операции, влияющие на баланс, сериализуются row-level lock касс через `lockCashBoxes(...)` (`SELECT ... FOR UPDATE` по таблице касс). Сейчас код блокирует `cashboxes` (канон); при изменении алиасов/схемы обновить `lockCashBoxes` и FormRequest.
- Обязательные точки: все create* (приходы/расходы/корректировки), `transferBetweenCashBoxes`, `completeTransaction`.
- Гарантия: операции над одной кассой выполняются последовательно; проверка <не уйти в минус> не обходится параллельным запросом.

## 5. Транзакции БД
- Все операции FinanceService выполняются внутри транзакции `legacy_new` с учётом row-level locks.
- Нельзя: читать баланс вне транзакции; читать, а потом блокировать; выполнять финансовые операции на default connection.

## 6. Легаси-слушатели и пересчёты
- Слушатели намеренно отключены (no-op): `RecalcCashboxHistoryListener`, `RecalcCashboxAfterTransactionChanged`, `UpdateCashboxBalanceListener`.
- Причина: нарушают правило единственного писателя, создают скрытые побочные эффекты, ломают инварианты баланса.
- Запрещено включать их обратно, пока стратегия источника правды не изменена осознанно.

## 7. Снапшоты и история
- Cashbox history — журнал/аудит, не источник расчёта; пишется только из FinanceService.
- Snapshots — допустимы лишь как кеш для чтения; при рассинхроне пересоздаются из `transactions`, не участвуют в принятии решений.

## 8. Обязательные тесты (anti-regression)
- Тест на гонку (must-have): две параллельные `transferBetweenCashBoxes` для одной кассы с риском ухода в минус. Ожидание: операции сериализуются, баланс не уходит в минус, одна из операций может корректно отклониться.
- Отсутствие теста = риск регрессии.

## 9. Правила для разработчиков (коротко)
- Меняешь деньги -> FinanceService.
- Считаешь баланс -> только из `transactions`.
- Есть параллелизм -> `lockCashBoxes`.
- Listener хочет <помочь> -> нет.
- Snapshot != правда.

## 10. Справочники (Lookup endpoints) - контракт фронт <-> бэк
### 10.1 Назначение
Фронтенд (Pinia dictionaries) использует lookup-эндпоинты для заполнения селектов/фильтров. Эти эндпоинты должны быть стабильными, без 404, и возвращать минимальный набор полей, необходимых UI.

### 10.2 Канонические эндпоинты
Финансы:
- GET /api/finance/transaction-types -> Transaction types
- GET /api/finance/payment-methods -> Payment methods
- GET /api/finance/funds -> Funds
- GET /api/finance/spending-items -> Spending items
- GET /api/finance/counterparties -> Counterparties

Общее:
- GET /api/common/companies -> Companies

### 10.3 Требования к данным
- Чтение выполняется из connection: legacy_new.
- Ответ содержит ключевые поля (минимум): id, name; для типов транзакций: code, sign; где применимо: is_active.
- Формат ответа должен оставаться обратно-совместимым.

### 10.4 Правила стабильности (важно)
- Эти эндпоинты считаются частью публичного контракта фронта.
- Добавлять поля можно.
- Переименовывать/удалять поля нельзя без периода депрекейта.
- 404 на lookup-эндпоинтах считается блокирующим багом.

### 10.5 Опциональные улучшения (не ломая контракт)
Допускается добавление: фильтра ?is_active=1, пагинации ?per_page=... (если фронт готов), сортировки по name. По умолчанию желательно возвращать <весь справочник> (если объём разумный).

## 11. Пользователи и компании (модель + FK)
- Модель: multi. Источник правды - pivot `user_company (user_id, company_id, tenant_id)`, UNIQUE(user_id, company_id). Колонка `users.company_id` трактуется как опциональная <компания по умолчанию>, не источник правды.
- Пользователи и токены живут в `legacy_new` (users + personal_access_tokens), поэтому доменные FK на users валидны.
- Требования к целостности: users.tenant_id FK->tenants; user_company.user_id FK->users; user_company.company_id FK->companies; role_users.user_id/role_id FK; профили user_profiles.user_id UNIQUE FK. Orphan-пивоты запрещены.
- Права/ACL идут через `user_company`; `users.company_id` — только дефолт для удобства, его нельзя использовать для авторизации.

## 12. Статус документа
Документ живой, но изменения обсуждаются отдельно и фиксируются changelog'ом. Любая правка FinanceService проверяется на соответствие этим правилам.

### Changelog (ключевые изменения)
- P1: Lookup endpoints (dictionaries). Добавлены контроллеры: app/Http/Controllers/API/Finance/TransactionTypeController.php, PaymentMethodController.php, FundController.php, SpendingItemLookupController.php, CounterpartyLookupController.php, app/Http/Controllers/API/Common/CompanyLookupController.php. Обновлены маршруты: routes/api.php (finance lookup endpoints + /common/companies). Цель: устранить 404 в Pinia dictionaries и обеспечить стабильный контракт справочников.
