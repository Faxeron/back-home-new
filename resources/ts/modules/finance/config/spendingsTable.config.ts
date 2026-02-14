export const SPENDING_TABLE = {
  include: 'cashbox,counterparty,contract,financeObject,fund,item',
  perPage: 200,
  rowHeight: 50,
}

export const SPENDING_COLUMNS = {
  id: { field: 'id', header: 'ID', sortable: true },
  paymentDate: { field: 'payment_date', header: 'Date', sortable: true },
  cashbox: { field: 'cashbox_id', header: 'Cashbox' },
  sum: { field: 'sum', header: 'Amount', sortable: true },
  fund: { field: 'fond_id', header: 'Fund' },
  item: { field: 'spending_item_id', header: 'Item' },
  financeObject: { field: 'finance_object_id', header: 'Finance Object' },
  contractId: { field: 'contract_id', header: 'Contract #' },
  counterparty: { field: 'counterparty_name', header: 'Counterparty' },
  description: { field: 'description', header: 'Description' },
}
