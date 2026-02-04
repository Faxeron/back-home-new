export const CASH_BOX_TABLE = {
  perPage: 200,
  rowHeight: 50,
}

export const CASH_BOX_COLUMNS = {
  id: { field: 'id', header: 'ID', sortable: true, width: '6ch' },
  logo: { field: 'logo_url', header: 'Логотип', width: '10ch' },
  name: { field: 'name', header: 'Название', sortable: true, filter: 'text' },
  company: { field: 'company_name', header: 'Компания' },
  description: { field: 'description', header: 'Описание' },
  isActive: { field: 'is_active', header: 'Активна' },
}
