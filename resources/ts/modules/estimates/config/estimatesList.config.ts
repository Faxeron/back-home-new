export const ESTIMATE_LIST_HEADERS = {
  id: 'ID',
  client: 'Клиент',
  siteAddress: 'Адрес участка',
  creator: 'Создал',
  itemsCount: 'Позиций',
  totalSum: 'Сумма',
  createdAt: 'Создана',
}

export const ESTIMATE_LIST_LABELS = {
  searchPlaceholder: 'Поиск по ID, клиенту, телефону, адресу',
  datePlaceholder: 'Период создания',
  resetFilters: 'Сброс фильтров',
  total: 'Всего',
  create: 'Новая смета',
  openAria: 'Открыть смету',
  deleteAria: 'Удалить смету',
  empty: 'Нет данных.',
  loading: 'Загрузка...',
}

export const EMPTY_TEXT = '-'

export const formatDate = (value?: string | null) => {
  if (!value) return EMPTY_TEXT
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? EMPTY_TEXT : date.toLocaleDateString('ru-RU')
}
