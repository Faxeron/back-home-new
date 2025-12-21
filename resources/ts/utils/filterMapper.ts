export type FilterType = 'text' | 'select' | 'number' | 'boolean' | 'dateRange'

interface ColumnConfig {
  field: string
  filter?: FilterType
}

interface PrimeFilter {
  value: any
}

interface PrimeFilters {
  [key: string]: PrimeFilter
}

interface PrimeLazyEvent {
  page?: number
  rows?: number
  sortField?: string
  sortOrder?: 1 | -1
  filters?: PrimeFilters
}

export function mapPrimeToDTO(lazyEvent: PrimeLazyEvent = {}, columns: ColumnConfig[] = [], include?: string | string[]) {
  const dto: any = {
    page: (lazyEvent.page ?? 0) + 1,
    per_page: lazyEvent.rows ?? 25,
    include,
    filter: {},
    sort: {},
  }

  if (lazyEvent.sortField) {
    dto.sort.field = lazyEvent.sortField
    dto.sort.order = lazyEvent.sortOrder === 1 ? 'asc' : 'desc'
  }

  for (const col of columns) {
    const f = lazyEvent.filters?.[col.field]
    if (!f || f.value === null || f.value === undefined || f.value === '') continue

    switch (col.filter) {
      case 'text':
      case 'select':
        dto.filter[col.field] = f.value
        break
      case 'number':
        dto.filter[col.field] = Number(f.value)
        break
      case 'boolean':
        dto.filter[col.field] = f.value ? 1 : 0
        break
      case 'dateRange':
        dto.filter[col.field] = {
          from: f.value?.[0] ? new Date(f.value[0]).toISOString().slice(0, 10) : null,
          to: f.value?.[1] ? new Date(f.value[1]).toISOString().slice(0, 10) : null,
        }
        break
      default:
        // noop
        break
    }
  }

  return dto
}
