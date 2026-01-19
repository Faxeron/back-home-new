import { PRODUCT_DICTIONARY_BASE_LABELS, formatDictionaryNumber } from './productsDictionaryBase.config'

export const PRODUCT_BRAND_LABELS = {
  title: 'Бренды',
  ...PRODUCT_DICTIONARY_BASE_LABELS,
}

export const PRODUCT_BRAND_HEADERS = [
  { title: 'ID', key: 'id', width: 80 },
  { title: 'Название', key: 'name' },
  { title: 'Товаров', key: 'products_count', align: 'end' },
]

export { formatDictionaryNumber }
