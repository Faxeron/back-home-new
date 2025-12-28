import { computed, ref, watch } from 'vue'

export type DictionaryFilterKind = 'text' | 'select' | 'number'

export interface DictionaryFilterDef {
  key: string
  kind: DictionaryFilterKind
  queryKey?: string
  debounce?: boolean
  transform?: (value: any) => any
}

interface UseDictionaryFiltersOptions {
  onChange?: () => void
  debounceMs?: number
}

const normalizeText = (value: unknown) => {
  if (value === null || value === undefined) return null
  const text = String(value).trim()
  return text.length ? text : null
}

const buildDefaultFilters = (defs: DictionaryFilterDef[]) => {
  const filters: Record<string, { value: any; matchMode: string }> = {}
  for (const def of defs) {
    filters[def.key] = {
      value: def.kind === 'text' ? '' : null,
      matchMode: def.kind === 'text' ? 'contains' : 'equals',
    }
  }
  return filters
}

export const useDictionaryFilters = (defs: DictionaryFilterDef[], options: UseDictionaryFiltersOptions = {}) => {
  const filters = ref(buildDefaultFilters(defs))
  const sortField = ref<string | null>(null)
  const sortOrder = ref<1 | -1 | null>(null)
  const debounceMs = options.debounceMs ?? 300
  const debouncedFields = new Set(defs.filter(def => def.debounce).map(def => def.key))

  const serverParams = computed(() => {
    const params: Record<string, any> = {}

    for (const def of defs) {
      const filterValue = filters.value[def.key]?.value
      if (def.kind === 'text') {
        const normalized = normalizeText(filterValue)
        if (normalized !== null) {
          params[def.queryKey ?? def.key] = def.transform ? def.transform(normalized) : normalized
        }
        continue
      }

      if (filterValue !== null && filterValue !== undefined && filterValue !== '') {
        params[def.queryKey ?? def.key] = def.transform ? def.transform(filterValue) : filterValue
      }
    }

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
    filters.value = buildDefaultFilters(defs)
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
