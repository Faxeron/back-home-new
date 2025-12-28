export const TRANSACTION_TYPE_TABLE = {
  perPage: 200,
  rowHeight: 50,
}

export const TRANSACTION_TYPE_COLUMNS = {
  id: { field: 'id', header: 'ID', sortable: true, width: '6ch' },
  code: { field: 'code', header: 'Код', sortable: true },
  name: { field: 'name', header: 'Название', sortable: true, filter: 'text' },
  sign: { field: 'sign', header: 'Знак' },
  isActive: { field: 'is_active', header: 'Активен' },
  sortOrder: { field: 'sort_order', header: 'Сортировка' },
}
