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

export const defaultTransactionFilters = () => ({
  id: { value: '', matchMode: 'contains' },
  is_paid: { value: null, matchMode: 'equals' },
  date_is_paid: { value: { from: null, to: null }, matchMode: 'custom' },
  is_completed: { value: null, matchMode: 'equals' },
  date_is_completed: { value: { from: null, to: null }, matchMode: 'custom' },
  transaction_type_id: { value: null, matchMode: 'equals' },
  payment_method_id: { value: null, matchMode: 'equals' },
  finance_object_id: { value: null, matchMode: 'equals' },
  contract_or_counterparty: { value: '', matchMode: 'custom' },
  cashbox_id: { value: null, matchMode: 'equals' },
  sum: { value: { min: null, max: null }, matchMode: 'custom' },
  notes: { value: '', matchMode: 'contains' },
  related: { value: '', matchMode: 'contains' },
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

interface UseTransactionFiltersOptions {
  onChange?: () => void
  debounceMs?: number
}

export const useTransactionFilters = (options: UseTransactionFiltersOptions = {}) => {
  registerCustomFilters()

  const filters = ref(defaultTransactionFilters())
  const sortField = ref<string | null>(null)
  const sortOrder = ref<1 | -1 | null>(null)
  const debounceMs = options.debounceMs ?? 300
  const debouncedFields = new Set(['id', 'contract_or_counterparty', 'notes', 'related'])

  const serverParams = computed(() => {
    const params: Record<string, any> = {}
    const current = filters.value

    const idLike = normalizeText(current.id?.value)
    if (idLike) params.id_like = idLike

    if (current.is_paid?.value !== null) params.is_paid = current.is_paid.value ? 1 : 0
    if (current.is_completed?.value !== null) params.is_completed = current.is_completed.value ? 1 : 0

    const paidFrom = toDateParam(current.date_is_paid?.value?.from)
    const paidTo = toDateParam(current.date_is_paid?.value?.to)
    if (paidFrom) params.date_paid_from = paidFrom
    if (paidTo) params.date_paid_to = paidTo

    const completedFrom = toDateParam(current.date_is_completed?.value?.from)
    const completedTo = toDateParam(current.date_is_completed?.value?.to)
    if (completedFrom) params.date_completed_from = completedFrom
    if (completedTo) params.date_completed_to = completedTo

    if (current.transaction_type_id?.value !== null) {
      params.transaction_type_id = current.transaction_type_id.value
    }
    if (current.payment_method_id?.value !== null) {
      params.payment_method_id = current.payment_method_id.value
    }
    if (current.finance_object_id?.value !== null) {
      params.finance_object_id = current.finance_object_id.value
    }
    if (current.cashbox_id?.value !== null) {
      params.cashbox_id = current.cashbox_id.value
    }

    const contractOrCounterparty = normalizeText(current.contract_or_counterparty?.value)
    if (contractOrCounterparty) params.contract_or_counterparty = contractOrCounterparty

    const sumMin = toNumberParam(current.sum?.value?.min)
    const sumMax = toNumberParam(current.sum?.value?.max)
    if (sumMin !== null) params.sum_min = sumMin
    if (sumMax !== null) params.sum_max = sumMax

    const notesLike = normalizeText(current.notes?.value)
    if (notesLike) params.notes_like = notesLike

    const relatedLike = normalizeText(current.related?.value)
    if (relatedLike) params.related_like = relatedLike

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
      const changedKeys = (Object.keys(next) as Array<keyof typeof next>).filter(key => {
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
    filters.value = defaultTransactionFilters()
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
