import { ref } from 'vue'
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
