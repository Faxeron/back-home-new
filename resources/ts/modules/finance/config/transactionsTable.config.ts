export const TRANSACTION_TABLE = {
  include: 'cashbox,counterparty,contract,financeObject,financeObjectAllocations,transactionType,paymentMethod',
  perPage: 200,
  rowHeight: 50,
}

export const TRANSACTION_BOOLEAN_OPTIONS = [
  { label: 'All', value: null },
  { label: 'Yes', value: true },
  { label: 'No', value: false },
]

export const TRANSACTION_COLUMNS = {
  id: { field: 'id', header: 'ID', sortable: true },
  isPaid: { field: 'is_paid', header: 'Paid', sortable: true },
  isCompleted: { field: 'is_completed', header: 'Completed', sortable: true },
  transactionType: { field: 'transaction_type_id', header: 'Type / Payment Method' },
  financeObject: { field: 'finance_object_id', header: 'Finance Object' },
  contractOrCounterparty: { field: 'contract_or_counterparty', header: 'Contract / Counterparty' },
  cashbox: { field: 'cashbox_id', header: 'Cashbox' },
  sum: { field: 'sum', header: 'Amount', sortable: true },
  notes: { field: 'notes', header: 'Comment' },
  related: { field: 'related', header: 'Related' },
}
