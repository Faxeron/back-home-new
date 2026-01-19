export const CONTRACT_STATUS_TABLE = {
  perPage: 200,
  rowHeight: 50,
}

export const CONTRACT_STATUS_COLUMNS = {
  id: { field: 'id', header: 'ID', sortable: true, width: '6ch' },
  name: { field: 'name', header: 'Название', sortable: true, filter: 'text' },
  code: { field: 'code', header: 'Код' },
  color: { field: 'color', header: 'Цвет' },
  sortOrder: { field: 'sort_order', header: 'Сортировка' },
  isActive: { field: 'is_active', header: 'Активен' },
}
