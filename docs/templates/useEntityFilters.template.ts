import { computed, ref, watch } from 'vue'
import { FilterService } from '@primevue/core/api'

let customFiltersRegistered = false

const registerCustomFilters = () => {
  if (customFiltersRegistered) return

  const toDate = (value: unknown) => {
    if (!value) return null
    const date = value instanceof Date ? value : new Date(value as string)
    return Number.isNaN(date.getTime()) ? null : date
  }

  const startOfDay = (value: Date) => new Date(value.getFullYear(), value.getMonth(), value.getDate())
  const endOfDay = (value: Date) =>
    new Date(value.getFullYear(), value.getMonth(), value.getDate(), 23, 59, 59, 999)

  const containsFilter = (value: any, filter: any, locale?: string) => {
    if (filter === undefined || filter === null || filter === '') return true
    if (value === undefined || value === null) return false
    const filterValue = String(filter).toLocaleLowerCase(locale)
    const stringValue = String(value).toLocaleLowerCase(locale)
    return stringValue.includes(filterValue)
  }

  FilterService.register('custom', (value: any, filter: any, locale?: string) => {
    if (filter === undefined || filter === null || filter === '') return true

    if (typeof filter === 'object') {
      if ('from' in filter || 'to' in filter) {
        const from = toDate(filter.from)
        const to = toDate(filter.to)
        if (!from && !to) return true
        const current = toDate(value)
        if (!current) return false
        if (from && current < startOfDay(from)) return false
        if (to && current > endOfDay(to)) return false
        return true
      }

      if ('min' in filter || 'max' in filter) {
        const min = filter.min ?? null
        const max = filter.max ?? null
        if (min === null && max === null) return true
        const amountValue = typeof value === 'object' ? value?.amount ?? value : value
        const amount = Number(amountValue)
        if (!Number.isFinite(amount)) return false
        if (min !== null && amount < min) return false
        if (max !== null && amount > max) return false
        return true
      }
    }

    return containsFilter(value ?? '', filter, locale)
  })

  customFiltersRegistered = true
}

export const default__Entity__Filters = () => ({
  id: { value: '', matchMode: 'contains' },
  status: { value: null, matchMode: 'equals' },
  status_date: { value: { from: null, to: null }, matchMode: 'custom' },
  sum: { value: { min: null, max: null }, matchMode: 'custom' },
  notes: { value: '', matchMode: 'contains' },
})

const normalizeText = (value: unknown) => {
  if (value === null || value === undefined) return null
  const text = String(value).trim()
  return text.length ? text : null
}

const toDateParam = (value: unknown) => {
  if (!value) return null
  const date = value instanceof Date ? value : new Date(String(value))
  if (Number.isNaN(date.getTime())) return null
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const toNumberParam = (value: unknown) => {
  if (value === null || value === undefined || value === '') return null
  const num = Number(value)
  return Number.isFinite(num) ? num : null
}

interface Use__Entity__FiltersOptions {
  onChange?: () => void
  debounceMs?: number
}

export const use__Entity__Filters = (options: Use__Entity__FiltersOptions = {}) => {
  registerCustomFilters()

  const filters = ref(default__Entity__Filters())
  const sortField = ref<string | null>(null)
  const sortOrder = ref<1 | -1 | null>(null)
  const debounceMs = options.debounceMs ?? 300
  const debouncedFields = new Set(['id', 'notes'])

  const serverParams = computed(() => {
    const params: Record<string, any> = {}
    const current = filters.value

    const idLike = normalizeText(current.id?.value)
    if (idLike) params.id_like = idLike

    if (current.status?.value !== null) params.status = current.status.value

    const statusFrom = toDateParam(current.status_date?.value?.from)
    const statusTo = toDateParam(current.status_date?.value?.to)
    if (statusFrom) params.status_date_from = statusFrom
    if (statusTo) params.status_date_to = statusTo

    const sumMin = toNumberParam(current.sum?.value?.min)
    const sumMax = toNumberParam(current.sum?.value?.max)
    if (sumMin !== null) params.sum_min = sumMin
    if (sumMax !== null) params.sum_max = sumMax

    const notesLike = normalizeText(current.notes?.value)
    if (notesLike) params.notes_like = notesLike

    if (sortField.value) {
      params.sort = sortField.value
      params.direction = sortOrder.value === 1 ? 'asc' : 'desc'
    }

    return params
  })

  let reloadTimer: number | undefined
  const scheduleReload = () => {
    if (!options.onChange) return
    if (reloadTimer) window.clearTimeout(reloadTimer)
    reloadTimer = window.setTimeout(() => {
      options.onChange?.()
    }, debounceMs)
  }

  const triggerImmediate = () => {
    if (!options.onChange) return
    if (reloadTimer) window.clearTimeout(reloadTimer)
    options.onChange?.()
  }

  const isSameFilterValue = (nextValue: any, prevValue: any) => {
    return JSON.stringify(nextValue ?? null) === JSON.stringify(prevValue ?? null)
  }

  watch(
    filters,
    (next, prev) => {
      if (!options.onChange) return
      if (!prev) {
        triggerImmediate()
        return
      }
      const changedKeys = Object.keys(next).filter(key => {
        const nextValue = next[key]?.value
        const prevValue = prev[key]?.value
        return !isSameFilterValue(nextValue, prevValue)
      })
      if (!changedKeys.length) return
      const hasImmediateChange = changedKeys.some(key => !debouncedFields.has(key))
      if (hasImmediateChange) {
        triggerImmediate()
      } else {
        scheduleReload()
      }
    },
    { deep: true },
  )

  const resetFilters = () => {
    filters.value = default__Entity__Filters()
    triggerImmediate()
  }

  const handleSort = (event: any) => {
    sortField.value = event?.sortField ?? null
    sortOrder.value = event?.sortOrder ?? null
    triggerImmediate()
  }

  return {
    filters,
    serverParams,
    resetFilters,
    handleSort,
  }
}
