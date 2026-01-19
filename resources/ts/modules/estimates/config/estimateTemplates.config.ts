export const TEMPLATE_EMPTY_TEXT = '-'

export const TEMPLATE_MATERIALS_HEADERS = {
  id: 'ID',
  title: 'Название',
  itemsCount: 'Позиции',
  updatedAt: 'Обновлен',
}

export const TEMPLATE_SEPTIKS_HEADERS = {
  id: 'ID',
  title: 'Название',
  templateTitle: 'Шаблон материалов',
  skus: 'SKU в группе',
  updatedAt: 'Обновлен',
}

export const TEMPLATE_MATERIALS_LABELS = {
  searchPlaceholder: 'Поиск по названию',
  total: 'Всего',
  create: 'Новый шаблон',
  empty: 'Нет шаблонов.',
  loading: 'Загрузка...',
  confirmDelete: 'Удалить шаблон материалов?',
  editAria: 'Редактировать шаблон',
  deleteAria: 'Удалить шаблон',
}

export const TEMPLATE_SEPTIKS_LABELS = {
  searchPlaceholder: 'Поиск по названию',
  total: 'Всего',
  create: 'Новая связка',
  empty: 'Нет связок.',
  loading: 'Загрузка...',
  confirmDelete: 'Удалить связку?',
  editAria: 'Редактировать связку',
  deleteAria: 'Удалить связку',
}

export const formatTemplateDate = (value?: string | null) => {
  if (!value) return TEMPLATE_EMPTY_TEXT
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? TEMPLATE_EMPTY_TEXT : date.toLocaleDateString('ru-RU')
}
