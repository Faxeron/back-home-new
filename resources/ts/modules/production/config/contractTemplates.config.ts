export const CONTRACT_TEMPLATE_HEADERS = {
  id: 'ID',
  name: 'Название',
  shortName: 'Короткое',
  documentType: 'Тип',
  productTypes: 'Типы товаров',
  advance: 'Предоплата',
  updatedAt: 'Обновлен',
  isActive: 'Активен',
}

export const CONTRACT_TEMPLATE_LABELS = {
  searchPlaceholder: 'Поиск по шаблонам',
  total: 'Всего',
  create: 'Создать шаблон',
  editAria: 'Редактировать шаблон',
  deleteAria: 'Удалить шаблон',
  confirmDelete: 'Удалить шаблон?',
  empty: 'Шаблонов нет.',
  loading: 'Загрузка...',
}

export const formatTemplateDate = (value?: string | null) => {
  if (!value) return '-'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? '-' : date.toLocaleDateString('ru-RU')
}

export const formatAdvanceMode = (template: {
  advance_mode?: 'none' | 'percent' | 'product_types' | null
  advance_percent?: number | null
  advance_product_type_ids?: number[] | null
}) => {
  if (template.advance_mode === 'percent') {
    const value = template.advance_percent ?? 0
    return `Процент ${value}%`
  }
  if (template.advance_mode === 'product_types') {
    const count = template.advance_product_type_ids?.length ?? 0
    return count ? `По типам (${count})` : 'По типам'
  }
  return 'Нет'
}

export const formatDocumentType = (value?: string | null) => {
  if (value === 'supply') return 'Поставка'
  if (value === 'install') return 'Монтаж'
  return 'Совмещенный'
}
