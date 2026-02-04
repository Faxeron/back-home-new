import { $api } from '@/utils/api'
import type { InstallationRow } from '../types/installations.types'

export const fetchInstallations = async (params: { worker_id?: number } = {}) => {
  const response = await $api('/installations', {
    query: params,
  })

  return response?.data as InstallationRow[] ?? []
}

export const updateInstallation = async (
  contractId: number,
  payload: { work_done_date: string; worker_id: number },
) => {
  const response = await $api(`/installations/${contractId}`, {
    method: 'PATCH',
    body: payload,
  })

  return response?.data as {
    contract_id: number
    work_done_date: string | null
    worker_id: number | null
    worker_name?: string | null
  }
}
