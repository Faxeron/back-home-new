# FINANCE ENTRYPOINTS

## Services (основные потоки)
- FinanceService — единая точка для contract receipts, director loan receipts, spendings, director withdrawal, transferBetweenCashBoxes, completeTransaction (пишет cashbox_history, проверяет баланс/валидацию сумм).
- CashTransferService — пагинация переводов (read-only); сама логика создания переводов в FinanceService.
- TransactionService — CRUD транзакций напрямую (createIncome/Expense/Transfer) с событиями TransactionCreated/Updated/Deleted; обходить FinanceService может нарушить баланс/историю.
- ReceiptService / SpendingService — CRUD/пагинация Receipt/Spending без автоматического создания Transaction (привязка через attachTransaction вызывается вручную).
- ContractFinanceService / ContractService — вспомогательные для договоров (не пишут баланс, но могут обновлять суммы договоров).

## Events/Listeners/Jobs, влияющие на баланс/историю
- TransactionCreated событие > RecalcCashboxHistoryListener (использует CashboxBalanceService для пересчёта/истории на основании sum; опирается на столбец balance в cash_boxes, который сейчас удалён).
- CashBoxBalanceSnapshotJob — периодически пишет cashbox_balance_snapshots с балансом, пересчитанным через FinanceService::getCashBoxBalance.
- FinancialActionLogged событие — логирует факт операций (без побочных эффектов, но сигнал для аудита).

## Repositories, обходящие сервисы (риск)
- CashboxRepository / CashboxBalanceService — работают с балансом кассы напрямую (updateBalance в cash_boxes + history add) и могут расходиться с FinanceService, который считает баланс на лету по transactions.
- CashboxHistoryRepository — добавляет записи истории в обход FinanceService::completeTransaction.

## Controllers, пишущие в модели напрямую (риск)
- TransactionController@store вызывает TransactionService->createIncome (минует FinanceService::completeTransaction).
- ReceiptController@index/SpendingController@index используют сервисы для чтения, но store* маршруты в ReceiptController/SpendingController/DirectorController/CashTransferController делегируют в FinanceService (корректно). Любые новые методы в этих контроллерах, использующие ReceiptService/SpendingService напрямую, обойдут балансную логику.
