export const PRODUCT_DICTIONARY_BASE_LABELS = {
  searchLabel: 'Поиск',
  namePlaceholder: 'Название',
  categoryLabel: 'Категория',
}

export const formatDictionaryNumber = (value?: number) =>
  Number(value ?? 0).toLocaleString('ru-RU')
