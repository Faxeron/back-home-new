export const SPENDING_ITEM_TABLE = {
  perPage: 200,
  rowHeight: 50,
}

export const SPENDING_ITEM_COLUMNS = {
  id: { field: 'id', header: 'ID', sortable: true, width: '6ch' },
  name: { field: 'name', header: 'Название', sortable: true, filter: 'text' },
  fund: { field: 'fond_id', header: 'Фонд', filter: 'select' },
  description: { field: 'description', header: 'Описание' },
  isActive: { field: 'is_active', header: 'Активна' },
}
