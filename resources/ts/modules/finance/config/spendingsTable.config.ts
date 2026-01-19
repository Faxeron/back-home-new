export const SPENDING_TABLE = {
  include: 'cashbox,counterparty,contract,fund,item',
  perPage: 200,
  rowHeight: 50,
}

export const SPENDING_COLUMNS = {
  id: { field: 'id', header: 'ID', sortable: true },
  paymentDate: { field: 'payment_date', header: 'Дата', sortable: true },
  cashbox: { field: 'cashbox_id', header: 'Касса' },
  sum: { field: 'sum', header: 'Сумма', sortable: true },
  fund: { field: 'fond_id', header: 'Фонд' },
  item: { field: 'spending_item_id', header: 'Статья' },
  contractId: { field: 'contract_id', header: '№ договора' },
  counterparty: { field: 'counterparty_name', header: 'Клиент' },
  description: { field: 'description', header: 'Примечание' },
}
