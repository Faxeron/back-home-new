import { computed, ref } from 'vue'

export const useEstimateFilters = () => {
  const search = ref('')
  const dateRange = ref<(Date | null)[]>([])

  const params = computed(() => {
    const [from, to] = dateRange.value ?? []
    const format = (value: Date | null) =>
      value ? value.toISOString().slice(0, 10) : null

    return {
      q: search.value || undefined,
      date_from: format(from),
      date_to: format(to),
    }
  })

  const hasFilters = computed(() => !!search.value || (dateRange.value?.length ?? 0) > 0)

  const resetFilters = () => {
    search.value = ''
    dateRange.value = []
  }

  return {
    search,
    dateRange,
    params,
    hasFilters,
    resetFilters,
  }
}
