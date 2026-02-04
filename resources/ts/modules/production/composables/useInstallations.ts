import { computed, ref } from 'vue'
import { useTableInfinite } from '@/composables/useTableLazy'
import type { InstallationRow, InstallationStatus } from '../types/installations.types'
import { updateInstallation } from '../api/installations.api'

export const useInstallations = () => {
  const error = ref('')
  const statusFilter = ref<InstallationStatus[]>(['waiting', 'assigned', 'completed'])
  const workerFilter = ref<number | 'all'>('all')

  const {
    data,
    total,
    loading,
    reset,
    virtualScrollerOptions,
  } = useTableInfinite<InstallationRow>({
    endpoint: 'installations',
    perPage: 80,
    rowHeight: 56,
    params: () => {
      const worker_id = workerFilter.value === 'all' ? undefined : workerFilter.value
      return worker_id ? { worker_id } : {}
    },
    getRowKey: row => row.contract_id,
  })

  const rows = computed(() => {
    if (!statusFilter.value.length) return data.value
    return data.value.filter(row => statusFilter.value.includes(row.status as InstallationStatus))
  })

  const load = async () => {
    error.value = ''
    try {
      await reset()
    } catch (err: any) {
      error.value = err?.response?.data?.message ?? 'РќРµ СѓРґР°Р»РѕСЃСЊ Р·Р°РіСЂСѓР·РёС‚СЊ РјРѕРЅС‚Р°Р¶Рё.'
    }
  }

  const assign = async (
    contractId: number,
    payload: { work_done_date: string; worker_id: number },
  ) => {
    return updateInstallation(contractId, payload)
  }

  return {
    rows,
    total,
    loading,
    error,
    statusFilter,
    workerFilter,
    virtualScrollerOptions,
    load,
    assign,
  }
}
