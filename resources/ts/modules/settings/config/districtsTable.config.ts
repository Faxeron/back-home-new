export const DISTRICT_TABLE = {
  perPage: 200,
  rowHeight: 50,
}

export const DISTRICT_COLUMNS = {
  id: { field: 'id', header: 'ID', sortable: true, width: '6ch' },
  name: { field: 'name', header: 'Название', sortable: true, filter: 'text' },
  city: { field: 'city_id', header: 'Город', filter: 'select' },
  isActive: { field: 'is_active', header: 'Активен' },
} as const
