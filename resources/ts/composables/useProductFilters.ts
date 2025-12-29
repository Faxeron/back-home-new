import { computed, ref, watch } from 'vue'

interface UseProductFiltersOptions {
  onChange?: () => void
  debounceMs?: number
}

const normalizeText = (value: string) => value.trim()

export const useProductFilters = (options: UseProductFiltersOptions = {}) => {
  const search = ref('')
  const categoryId = ref<number | null>(null)
  const subCategoryId = ref<number | null>(null)
  const brandId = ref<number | null>(null)
  const sortField = ref<string | null>(null)
  const sortOrder = ref<1 | -1 | null>(null)

  const serverParams = computed(() => {
    const params: Record<string, any> = {}
    const q = normalizeText(search.value)

    if (q) params.q = q
    if (categoryId.value !== null) params.category_id = categoryId.value
    if (subCategoryId.value !== null) params.sub_category_id = subCategoryId.value
    if (brandId.value !== null) params.brand_id = brandId.value

    if (sortField.value) {
      params.sort = sortField.value
      params.direction = sortOrder.value === 1 ? 'asc' : 'desc'
    }

    return params
  })

  const debounceMs = options.debounceMs ?? 300
  let reloadTimer: number | undefined

  const triggerImmediate = () => {
    if (!options.onChange) return
    if (reloadTimer) window.clearTimeout(reloadTimer)
    options.onChange?.()
  }

  const scheduleReload = () => {
    if (!options.onChange) return
    if (reloadTimer) window.clearTimeout(reloadTimer)
    reloadTimer = window.setTimeout(() => {
      options.onChange?.()
    }, debounceMs)
  }

  watch(
    [search, categoryId, subCategoryId, brandId],
    (next, prev) => {
      if (!options.onChange) return
      if (!prev) {
        triggerImmediate()
        return
      }

      const [nextSearch, nextCategory, nextSubCategory, nextBrand] = next
      const [prevSearch, prevCategory, prevSubCategory, prevBrand] = prev

      const onlySearchChanged =
        nextSearch !== prevSearch &&
        nextCategory === prevCategory &&
        nextSubCategory === prevSubCategory &&
        nextBrand === prevBrand

      if (onlySearchChanged) {
        scheduleReload()
      } else {
        triggerImmediate()
      }
    },
    { deep: false },
  )

  const resetFilters = () => {
    search.value = ''
    categoryId.value = null
    subCategoryId.value = null
    brandId.value = null
    triggerImmediate()
  }

  const handleSort = (event: any) => {
    sortField.value = event?.sortField ?? null
    sortOrder.value = event?.sortOrder ?? null
    triggerImmediate()
  }

  return {
    search,
    categoryId,
    subCategoryId,
    brandId,
    serverParams,
    resetFilters,
    handleSort,
  }
}
