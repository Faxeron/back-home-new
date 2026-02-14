export const RECEIPT_TABLE = {
  include: 'cashbox,contract,counterparty,financeObject',
  perPage: 200,
  rowHeight: 50,
}

export const RECEIPT_COLUMNS = {
  id: { field: 'id', header: 'ID' },
  paymentDate: { field: 'payment_date', header: 'Date', sortable: true },
  cashbox: { field: 'cashbox_id', header: 'Cashbox' },
  cashflow: { field: 'cashflow_item_id', header: 'Cashflow Item' },
  sum: { field: 'sum', header: 'Amount', sortable: true },
  financeObject: { field: 'finance_object_id', header: 'Finance Object' },
  contractId: { field: 'contract_id', header: 'Contract #' },
  counterparty: { field: 'counterparty_name', header: 'Counterparty' },
  description: { field: 'description', header: 'Description' },
}
