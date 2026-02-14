import { createUrl } from '@/@core/composable/createUrl'
import { $api } from '@/utils/api'

export const transactionsListEndpoint = (query: any) => createUrl('finance/transactions', { query })

export const unassignedTransactionsEndpoint = (query: any) => createUrl('finance/transactions/unassigned', { query })

export const assignTransactionFinanceObject = async (
  transactionId: number,
  payload: Record<string, any>,
) => {
  const response = await $api(`finance/transactions/${transactionId}/assign-object`, {
    method: 'POST',
    body: payload,
  })

  return response?.data ?? response
}

export const bulkAssignUnassignedTransactions = async (payload: Record<string, any>) => {
  const response = await $api('finance/transactions/unassigned/bulk-assign', {
    method: 'POST',
    body: payload,
  })

  return response?.data ?? response
}
