export const COMPANY_TABLE = {
  perPage: 200,
  rowHeight: 50,
}

export const COMPANY_COLUMNS = {
  id: { field: 'id', header: 'ID', sortable: true, width: '6ch' },
  name: { field: 'name', header: 'Название', sortable: true, filter: 'text' },
  code: { field: 'code', header: 'Код' },
  phone: { field: 'phone', header: 'Телефон' },
  email: { field: 'email', header: 'Email' },
}
