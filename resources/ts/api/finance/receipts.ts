import { createUrl } from '@/@core/composable/createUrl'

export const receiptsListEndpoint = (query: any) => createUrl('finances/receipts', { query })
