import { createUrl } from '@/@core/composable/createUrl'
import { $api } from '@/utils/api'

export const receiptsListEndpoint = (query: any) => createUrl('finance/receipts', { query })

export const createContractReceipt = async (payload: Record<string, any>) => {
  const response = await $api('finance/receipts/contract', {
    method: 'POST',
    body: payload,
  })
  return response?.data ?? response
}
