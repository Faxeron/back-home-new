# RED FLAGS

Finance
- CashboxHistory coverage: TransactionService::createIncome/Expense/Transfer emits TransactionCreated -> RecalcCashboxHistoryListener (uses outdated balance column) but FinanceService::completeTransaction writes cashbox_history separately; direct TransactionService calls can bypass FinanceService checks (positive sum, balance guard) and double-write/skip history.
- Balance source mismatch: FinanceService computes balance on the fly (SUM transactions*sign, is_completed=1) while CashboxBalanceService updates `cash_boxes.balance` (column удалён) and writes history; risk рассинхронизации и ошибок при чтении/расчёте.
- Missing locks: FinanceService transferBetweenCashBoxes/completeTransaction не используют SELECT ... FOR UPDATE или блокировки кассы; параллельные операции по одной кассе могут пропустить проверку Insufficient funds или записать неверный balance_after.
- Decimal consistency: transactions.sum decimal(15,2) vs receipts/spendings sum decimal(14,2) > потенциальные округления/расхождения между связанными записями.
- Code/seed divergence: transaction_types seeded с code EXPENSE, но FinanceService ищет OUTCOME/DIRECTOR_*; при отсутствии таких кодов будет RuntimeException.
- `sum` vs `summ`: миграции переименовывают summ->sum; любые ручные вставки/старые дампы создают несовместимость с сервисами/респозиториями.
- Endpoints duplication: legacy plural `finances` aliases (листы) vs canonical `/api/finance/*` (листы+мутаторы) — фронт может отправлять на «не тот» префикс; риск рассинхрона/двойной поддержки.
- Orphaned records: receipts/spendings allow nullable transaction_id; failure после создания Transaction или ручные правки оставят движения без связанной транзакции и истории.
- Cash transfers integrity: transaction_out_id/transaction_in_id FK есть, но нет валидации на совпадение сумм/тенанта/компании на уровне БД; ручные вставки нарушат баланс.

Settings / dictionaries
- Catalogs как enum vs таблицы: transaction_types/payment_methods — таблицы, но фронт ожидает эндпоинты `/api/finance/transaction-types` и `/api/finance/payment-methods`, которых нет в routes > 404 и ручные хардкоды возможны.
- Dictionaries store обращается к `/api/finance/funds`, `/api/finance/spending-items`, `/api/finance/counterparties`, `/api/common/companies` — эти маршруты отсутствуют; UI может падать/оставлять пустые селекты.
- sale_types/cash_boxes/companies/справочники не привязаны к tenant/company на уровне БД (FK только на company_id, tenant_id default 1) — риск кросс-tenant доступа.
