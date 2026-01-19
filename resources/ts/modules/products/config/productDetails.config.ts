export const PRODUCT_DETAILS_EMPTY_TEXT = '—'

export const PRODUCT_DETAILS_LABELS = {
  titleFallback: 'Товар',
  skuLabel: 'SKU',
  backButton: 'К товарам',
  loading: 'Загрузка...',
  error: 'Не удалось загрузить карточку товара.',
  noAttributes: 'Характеристики не заданы.',
  noMedia: 'Медиа не добавлено.',
  noRelations: 'Связей нет.',
  mediaTypeFallback: 'media',
  mediaSortPrefix: '#',
}

export const PRODUCT_DETAILS_TABS = {
  overview: 'Основные данные',
  prices: 'Цены',
  descriptions: 'Описание',
  attributes: 'Характеристики',
  media: 'Медиа',
  relations: 'Связи',
}

export const PRODUCT_DETAILS_CARDS = {
  basic: 'Основные данные',
  status: 'Статусы',
  prices: 'Цены',
  descriptions: 'Описание',
  attributes: 'Характеристики',
  relations: 'Связанные товары',
}

export const PRODUCT_DETAILS_TABLE_HEADERS = {
  field: 'Поле',
  value: 'Значение',
  relationType: 'Тип',
  relationProduct: 'Товар',
  relationScu: 'SCU',
  attributeName: 'Характеристика',
  attributeValue: 'Значение',
}

export const PRODUCT_DETAILS_FLAGS = {
  visible: 'Видимость',
  top: 'Топ',
  new: 'Новый',
}

export const PRODUCT_BASIC_FIELDS = {
  kind: 'Вид товара',
  category: 'Категория',
  subCategory: 'Подкатегория',
  brand: 'Бренд',
  productType: 'Тип товара (product_type_id)',
  unit: 'Ед. изм. (unit_id)',
  updatedAt: 'Обновлено',
}

export const PRODUCT_PRICE_FIELDS = {
  price: 'Цена',
  priceSale: 'Цена по акции',
  priceVendor: 'Цена производителя',
  priceVendorMin: 'Мин. цена производителя',
  priceZakup: 'Цена закупа',
  priceDelivery: 'Доставка',
  montaj: 'Монтаж',
  montajSebest: 'Монтаж с/с',
}

export const PRODUCT_DESCRIPTION_FIELDS = {
  short: 'Краткое описание',
  long: 'Полное описание',
  dignities: 'Достоинства',
  constructive: 'Конструкция',
  avito1: 'Avito 1',
  avito2: 'Avito 2',
}

export const formatProductValue = (value: any) => {
  if (value === null || value === undefined || value === '') return PRODUCT_DETAILS_EMPTY_TEXT
  return value
}

export const formatProductDate = (value?: string) => {
  if (!value) return PRODUCT_DETAILS_EMPTY_TEXT
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? PRODUCT_DETAILS_EMPTY_TEXT : date.toLocaleDateString('ru-RU')
}

export const formatProductPrice = (value?: number | null) => {
  if (value === null || value === undefined) return PRODUCT_DETAILS_EMPTY_TEXT
  return new Intl.NumberFormat('ru-RU').format(value)
}
