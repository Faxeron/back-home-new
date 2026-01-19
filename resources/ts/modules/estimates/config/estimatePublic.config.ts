export const PUBLIC_ESTIMATE_LABELS = {
  titleClient: 'Смета для клиента',
  titleMontaj: 'Смета для монтажника',
  fallbackTitle: 'Смета',
  date: 'Дата',
  client: 'Клиент',
  phone: 'Телефон',
  siteAddress: 'Адрес участка',
  positions: 'Позиции сметы',
  groupTotal: 'Итог',
  total: 'Итого по смете',
  empty: 'Позиции не найдены.',
  notFound: 'Смета не найдена.',
  noPrices: 'Без цен',
}

export const PUBLIC_ESTIMATE_EMPTY_TEXT = '-'

export const formatPublicDate = (value?: string | null) =>
  value
    ? new Date(value).toLocaleDateString('ru-RU')
    : PUBLIC_ESTIMATE_EMPTY_TEXT
