export const PRODUCT_TABLE_EMPTY_TEXT = '—'

export const PRODUCT_TABLE_HEADERS = {
  id: 'ID',
  name: 'Название',
  scu: 'SCU',
  kind: 'Вид',
  category: 'Категория',
  brand: 'Бренд',
  visibility: 'Видимость',
  updatedAt: 'Обновлено',
}

export const PRODUCT_TABLE_LABELS = {
  searchPlaceholder: 'Поиск по названию или SKU',
  clearSearchAria: 'Сбросить поиск',
  brandPlaceholder: 'Бренд',
  categoryPlaceholder: 'Категория',
  subCategoryPlaceholder: 'Подкатегория',
  resetFilters: 'Сбросить фильтры',
  total: 'Всего',
  viewTableAria: 'Таблица',
  viewCardsAria: 'Карточки',
  openCardAria: 'Открыть карточку',
  cardSkuLabel: 'SKU',
  cardBrandLabel: 'Бренд',
  empty: 'Нет товаров.',
  loading: 'Загрузка...',
}

export const formatProductDate = (value?: string) => {
  if (!value) return PRODUCT_TABLE_EMPTY_TEXT
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? PRODUCT_TABLE_EMPTY_TEXT : date.toLocaleDateString('ru-RU')
}
