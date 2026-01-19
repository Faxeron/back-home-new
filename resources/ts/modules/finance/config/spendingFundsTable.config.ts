export const SPENDING_FUND_TABLE = {
  perPage: 200,
  rowHeight: 50,
}

export const SPENDING_FUND_COLUMNS = {
  id: { field: 'id', header: 'ID', sortable: true, width: '6ch' },
  name: { field: 'name', header: 'Название', sortable: true, filter: 'text' },
  description: { field: 'description', header: 'Описание' },
  itemsCount: { field: 'items_count', header: 'Кол-во статей' },
  isActive: { field: 'is_active', header: 'Активен' },
}
