# FINANCE INVARIANTS

- Transaction types (codes -> sign): INCOME(+1), EXPENSE/OUTCOME(-1), TRANSFER_IN(+1), TRANSFER_OUT(-1), ADVANCE(-1), REFUND(+1); сервис FinanceService дополнительно ожидает DIRECTOR_LOAN(+1) и DIRECTOR_WITHDRAWAL(-1) (должны существовать в БД, иначе RuntimeException).
- Операции -> количество транзакций:
  - createContractReceipt, createDirectorLoanReceipt, createSpending, createDirectorWithdrawal — создают 1 Transaction, связанную с Receipt/Spending через related_id/transaction_id.
  - transferBetweenCashBoxes — создаёт 2 Transactions (TRANSFER_OUT + TRANSFER_IN) и один CashTransfer, связанный через transaction_out_id/transaction_in_id.
- cashbox_history: пишется только в FinanceService::completeTransaction (одна запись per Transaction, баланс после операции). Листенеры из TransactionService тоже пишут историю через CashboxBalanceService->history->add, но опираются на устаревший balance в cash_boxes.
- Баланс кассы: FinanceService::getCashBoxBalance пересчитывает SUM(transaction.sum * transaction_types.sign) по завершённым транзакциям (is_completed=1) в legacy_new.transactions; snapshots/история не используются для расчёта.
- is_paid/is_completed и даты: completeTransaction выставляет is_paid=1, date_is_paid=now, is_completed=1, date_is_completed=now перед записью в history.
- Tenant/company consistency: FinanceService присваивает tenant_id/company_id из пользователя или payload; transferBetweenCashBoxes проверяет, что обе кассы одного tenant и (если заполнено) одной company; остальные операции не делают сквозных проверок соответствия сущностей.
- Источник правды по суммам: для баланса касс — transactions*sign; receipts/spendings хранят сумму дублирующе и связываются через related_id/transaction_id; контракты (paid_amount) здесь не обновляются — требуется внешняя синхронизация.
- Положительная сумма обязательна: FinanceService::assertPositiveSum запрещает sum<=0 для всех операций.
