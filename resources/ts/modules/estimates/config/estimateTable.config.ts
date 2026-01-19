export const DEFAULT_GROUP_LABEL = 'Без группы'

export const PRODUCT_TYPE_LABELS: Record<number, string> = {
  1: 'Материал',
  2: 'Товар',
  3: 'Работа',
  4: 'Услуга',
  5: 'Транспорт',
  6: 'Субподряд',
}

export const GROUP_ORDER: Record<number, number> = {
  2: 1, // Товар
  1: 2, // Материал
  3: 3, // Работа
  5: 4, // Транспорт
  4: 5, // Услуга
  6: 6, // Субподряд
}

export const getProductTypeLabel = (productTypeId?: number | null) =>
  productTypeId ? PRODUCT_TYPE_LABELS[productTypeId] ?? `Тип ${productTypeId}` : '-'

export const getGroupOrder = (productTypeId?: number | null) =>
  productTypeId ? GROUP_ORDER[productTypeId] ?? 99 : 99

export const formatCurrency = (value?: number | null) =>
  typeof value === 'number'
    ? value.toLocaleString('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 })
    : '-'
