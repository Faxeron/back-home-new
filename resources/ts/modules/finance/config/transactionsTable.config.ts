export const TRANSACTION_TABLE = {
  include: 'cashbox,counterparty,contract,transactionType,paymentMethod',
  perPage: 200,
  rowHeight: 50,
}

export const TRANSACTION_BOOLEAN_OPTIONS = [
  { label: 'Все', value: null },
  { label: 'Да', value: true },
  { label: 'Нет', value: false },
]

export const TRANSACTION_COLUMNS = {
  id: { field: 'id', header: 'ID', sortable: true },
  isPaid: { field: 'is_paid', header: 'Оплачено', sortable: true },
  isCompleted: { field: 'is_completed', header: 'Исполнено', sortable: true },
  transactionType: { field: 'transaction_type_id', header: 'Тип / Метод платежа' },
  contractOrCounterparty: { field: 'contract_or_counterparty', header: 'Договор / Контрагент' },
  cashbox: { field: 'cashbox_id', header: 'Касса' },
  sum: { field: 'sum', header: 'Сумма', sortable: true },
  notes: { field: 'notes', header: 'Комментарий' },
  related: { field: 'related', header: 'Связь' },
}
