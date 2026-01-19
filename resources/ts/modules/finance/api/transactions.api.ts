import { createUrl } from '@/@core/composable/createUrl'

export const transactionsListEndpoint = (query: any) => createUrl('finance/transactions', { query })
