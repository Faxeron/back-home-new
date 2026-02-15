export const CITY_TABLE = {
  perPage: 200,
  rowHeight: 50,
}

export const CITY_COLUMNS = {
  id: { field: 'id', header: 'ID', sortable: true, width: '6ch' },
  name: { field: 'name', header: 'Название', sortable: true, filter: 'text' },
} as const
