import { PRODUCT_DICTIONARY_BASE_LABELS, formatDictionaryNumber } from './productsDictionaryBase.config'

export const PRODUCT_SUBCATEGORY_LABELS = {
  title: 'Подкатегории товаров',
  ...PRODUCT_DICTIONARY_BASE_LABELS,
}

export const PRODUCT_SUBCATEGORY_HEADERS = [
  { title: 'ID', key: 'id', width: 80 },
  { title: 'Подкатегория', key: 'name' },
  { title: 'Категория', key: 'category' },
  { title: 'Товаров', key: 'products_count', align: 'end' },
] as const

export { formatDictionaryNumber }
