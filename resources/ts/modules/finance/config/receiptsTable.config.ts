export const RECEIPT_TABLE = {
  include: 'cashbox,contract,counterparty',
  perPage: 200,
  rowHeight: 50,
}

export const RECEIPT_COLUMNS = {
  id: { field: 'id', header: 'ID' },
  paymentDate: { field: 'payment_date', header: 'Дата', sortable: true },
  cashbox: { field: 'cashbox_id', header: 'Касса' },
  cashflow: { field: 'cashflow_item_id', header: 'Статья ДДС' },
  sum: { field: 'sum', header: 'Сумма', sortable: true },
  contractId: { field: 'contract_id', header: '№ договора' },
  counterparty: { field: 'counterparty_name', header: 'ФИО' },
  description: { field: 'description', header: 'Примечание' },
}
