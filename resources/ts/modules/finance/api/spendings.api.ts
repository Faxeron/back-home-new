import { createUrl } from '@/@core/composable/createUrl'
import { $api } from '@/utils/api'

export const spendingsListEndpoint = (query: any) => createUrl('finance/spendings', { query })

export const createContractSpending = async (payload: Record<string, any>) => {
  const response = await $api('finance/spendings', {
    method: 'POST',
    body: payload,
  })
  return response?.data ?? response
}
