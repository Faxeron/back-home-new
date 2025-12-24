import { computed, ref } from 'vue'
import { mapPrimeToDTO } from '@/utils/filterMapper'
import { $api } from '@/utils/api'
import type { DataTableLazyLoadEvent } from 'primevue/datatable'

export interface UseTableLazyConfig {
  endpoint: string
  columns: any[]
  include?: string | string[]
}

export function useTableLazy<T = any>(config: UseTableLazyConfig) {
  const data = ref<T[]>([])
  const total = ref(0)
  const loading = ref(false)

  const load = async (event?: DataTableLazyLoadEvent) => {
    loading.value = true
    try {
      const dto = mapPrimeToDTO(event ?? {}, config.columns, config.include)
      const endpointPath = config.endpoint.startsWith('/api/')
        ? config.endpoint.replace(/^\/api/, '')
        : config.endpoint
      const response = await $api(endpointPath, { params: dto })
      const list = Array.isArray(response?.data?.data)
        ? response.data.data
        : Array.isArray(response?.data)
          ? response.data
          : Array.isArray(response)
            ? response
            : []

      data.value = list
      total.value = response?.data?.total ?? response?.total ?? list.length
    } finally {
      loading.value = false
    }
  }

  return { data, total, loading, load }
}

export interface UseTableInfiniteConfig<T = any> {
  endpoint: string
  include?: string | string[]
  perPage?: number
  rowHeight?: number
  thresholdRows?: number
  params?: Record<string, any> | (() => Record<string, any>)
  getRowKey?: (row: T) => string | number | null | undefined
}

const getEndpointPath = (endpoint: string) =>
  endpoint.startsWith('/api/') ? endpoint.replace(/^\/api/, '') : endpoint

const mergeRows = <T>(
  current: T[],
  incoming: T[],
  getRowKey: (row: T) => string | number | null | undefined,
) => {
  if (!incoming.length) return current
  const existing = new Set<string | number>()
  for (const item of current) {
    const key = getRowKey(item)
    if (key !== null && key !== undefined) existing.add(key)
  }
  const next = current.slice()
  for (const item of incoming) {
    const key = getRowKey(item)
    if (key === null || key === undefined) {
      next.push(item)
      continue
    }
    if (!existing.has(key)) {
      existing.add(key)
      next.push(item)
    }
  }
  return next
}

export function useTableInfinite<T = any>(config: UseTableInfiniteConfig<T>) {
  const data = ref<T[]>([])
  const total = ref(0)
  const loading = ref(false)
  const hasMore = ref(true)
  const loadedPages = new Set<number>()
  const maxLoadedPage = ref(0)

  const perPage = config.perPage ?? 200
  const rowHeight = config.rowHeight ?? 50
  const thresholdRows = config.thresholdRows ?? 4
  const getRowKey = config.getRowKey ?? ((row: T) => (row as any)?.id)
  const endpointPath = getEndpointPath(config.endpoint)

  const buildParams = (pageNumber: number) => {
    const baseParams = typeof config.params === 'function' ? config.params() : config.params
    const params: Record<string, any> = {
      ...(baseParams ?? {}),
      per_page: perPage,
      page: pageNumber,
    }
    if (config.include) params.include = config.include
    return params
  }

  const loadPage = async (pageNumber: number, reset = false) => {
    if (loading.value) return
    if (pageNumber < 1) return
    if (!reset && loadedPages.has(pageNumber)) return
    if (!reset && !hasMore.value) return

    loading.value = true
    try {
      const response: any = await $api(endpointPath, { params: buildParams(pageNumber) })
      const list = Array.isArray(response?.data?.data)
        ? response.data.data
        : Array.isArray(response?.data)
          ? response.data
          : Array.isArray(response)
            ? response
            : []

      if (reset) {
        data.value = list
        loadedPages.clear()
        maxLoadedPage.value = 0
        hasMore.value = true
      } else {
        data.value = mergeRows(data.value, list, getRowKey)
      }
      loadedPages.add(pageNumber)
      if (pageNumber > maxLoadedPage.value) maxLoadedPage.value = pageNumber

      const meta = response?.data?.meta ?? response?.meta
      const metaTotal =
        typeof meta?.total === 'number'
          ? meta.total
          : typeof meta?.total === 'string'
            ? Number(meta.total)
            : null
      const startIndex = (pageNumber - 1) * perPage

      if (metaTotal !== null && Number.isFinite(metaTotal)) {
        total.value = metaTotal
        hasMore.value = startIndex + list.length < metaTotal
      } else {
        total.value = data.value.length
        hasMore.value = list.length === perPage
      }
    } finally {
      loading.value = false
    }
  }

  const loadMore = () => loadPage(maxLoadedPage.value + 1)
  const reset = () => loadPage(1, true)

  const handleScroll = (event: Event) => {
    if (loading.value || !hasMore.value) return
    const target = event.target as HTMLElement | null
    if (!target) return
    const remaining = target.scrollHeight - target.scrollTop - target.clientHeight
    if (remaining <= rowHeight * thresholdRows) {
      loadMore()
    }
  }

  const virtualScrollerOptions = computed(() => ({
    itemSize: rowHeight,
    showLoader: true,
    onScroll: handleScroll,
  }))

  return {
    data,
    total,
    loading,
    hasMore,
    loadPage,
    loadMore,
    reset,
    handleScroll,
    virtualScrollerOptions,
  }
}
