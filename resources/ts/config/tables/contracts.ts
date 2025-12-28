export const CONTRACTS_TABLE = {
  perPage: 200,
  rowHeight: 50,
}

export const CONTRACT_COLUMNS = {
  id: { field: 'id', header: 'ID' },
  counterparty: { field: 'counterparty', header: 'Клиент' },
  address: { field: 'address', header: 'Адрес монтажа' },
  model: { field: 'model', header: 'Модель' },
  estimate: { field: 'estimate', header: 'Смета' },
  workDates: { field: 'work_dates', header: 'Дата монтажа' },
  saleType: { field: 'sale_type', header: 'Тип продажи' },
  totalAmount: { field: 'total_amount', header: 'Сумма' },
  paidDebt: { field: 'paid_debt', header: 'Выплачено / долг' },
  staff: { field: 'staff', header: 'Менеджер / монтажник' },
  status: { field: 'status', header: 'Статус' },
  actions: { field: 'actions', header: 'Действия' },
}
