export const CASHFLOW_ITEM_TABLE = {
  perPage: 200,
  rowHeight: 50,
}

export const CASHFLOW_ITEM_COLUMNS = {
  id: { field: 'id', header: 'ID', sortable: true, width: '6ch' },
  code: { field: 'code', header: 'Код', sortable: true, filter: 'text' },
  name: { field: 'name', header: 'Название', sortable: true, filter: 'text' },
  section: { field: 'section', header: 'Раздел', filter: 'select' },
  direction: { field: 'direction', header: 'Направление', filter: 'select' },
  parent: { field: 'parent_id', header: 'Родитель', filter: 'select' },
  sortOrder: { field: 'sort_order', header: 'Сортировка' },
  isActive: { field: 'is_active', header: 'Активна', filter: 'select' },
}
