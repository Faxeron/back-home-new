import { createUrl } from '@/@core/composable/createUrl'

export const spendingsListEndpoint = (query: any) => createUrl('finances/spendings', { query })
