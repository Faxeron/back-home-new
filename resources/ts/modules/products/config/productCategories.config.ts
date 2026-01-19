import { PRODUCT_DICTIONARY_BASE_LABELS, formatDictionaryNumber } from './productsDictionaryBase.config'

export const PRODUCT_CATEGORY_LABELS = {
  title: 'Категории товаров',
  ...PRODUCT_DICTIONARY_BASE_LABELS,
}

export const PRODUCT_CATEGORY_HEADERS = [
  { title: 'ID', key: 'id', width: 80 },
  { title: 'Название', key: 'name' },
  { title: 'Товаров', key: 'products_count', align: 'end' },
]

export { formatDictionaryNumber }
