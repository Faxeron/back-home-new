export const SALE_TYPE_TABLE = {
  perPage: 200,
  rowHeight: 50,
}

export const SALE_TYPE_COLUMNS = {
  id: { field: 'id', header: 'ID', sortable: true, width: '6ch' },
  name: { field: 'name', header: 'Название', sortable: true, filter: 'text' },
  isActive: { field: 'is_active', header: 'Активен' },
} as const
