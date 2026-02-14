import { createUrl } from '@/@core/composable/createUrl'
import { $api } from '@/utils/api'

export const financeObjectsListEndpoint = (query: any) => createUrl('finance/objects', { query })

export const financeObjectTransactionsEndpoint = (financeObjectId: number, query: any) =>
  createUrl(`finance/objects/${financeObjectId}/transactions`, { query })

export const financeObjectsLookupEndpoint = (query: any) => createUrl('finance/objects/lookup', { query })

export const getFinanceObject = async (financeObjectId: number) => {
  const response = await $api(`finance/objects/${financeObjectId}`)
  return response?.data ?? response
}

export const createFinanceObject = async (payload: Record<string, any>) => {
  const response = await $api('finance/objects', {
    method: 'POST',
    body: payload,
  })
  return response?.data ?? response
}

export const updateFinanceObject = async (financeObjectId: number, payload: Record<string, any>) => {
  const response = await $api(`finance/objects/${financeObjectId}`, {
    method: 'PUT',
    body: payload,
  })
  return response?.data ?? response
}

