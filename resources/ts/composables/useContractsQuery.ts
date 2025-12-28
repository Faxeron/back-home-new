import { computed, ref, watch } from 'vue'

interface UseContractsQueryOptions {
  onChange?: () => void
  debounceMs?: number
}

const normalizeText = (value: string) => value.trim()

export const useContractsQuery = (options: UseContractsQueryOptions = {}) => {
  const search = ref('')
  const statusId = ref<number | null>(null)
  const debounceMs = options.debounceMs ?? 350
  let debounceTimer: number | undefined

  const serverParams = computed(() => {
    const params: Record<string, any> = {}
    const normalized = normalizeText(search.value)
    if (normalized.length) params.q = normalized
    if (statusId.value !== null) params.status_id = statusId.value
    return params
  })

  const trigger = () => {
    if (!options.onChange) return
    if (debounceTimer) window.clearTimeout(debounceTimer)
    options.onChange()
  }

  const schedule = () => {
    if (!options.onChange) return
    if (debounceTimer) window.clearTimeout(debounceTimer)
    debounceTimer = window.setTimeout(() => {
      options.onChange?.()
    }, debounceMs)
  }

  watch(
    search,
    (next, prev) => {
      if (next === prev) return
      schedule()
    },
    { flush: 'post' },
  )

  watch(
    statusId,
    (next, prev) => {
      if (next === prev) return
      trigger()
    },
    { flush: 'post' },
  )

  const reset = () => {
    search.value = ''
    statusId.value = null
    trigger()
  }

  const toggleStatus = (nextId: number | null) => {
    statusId.value = statusId.value === nextId ? null : nextId
  }

  return {
    search,
    statusId,
    serverParams,
    reset,
    toggleStatus,
  }
}
