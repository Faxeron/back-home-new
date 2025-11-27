import { createUrl } from '@/@core/composable/createUrl'

export const contractsListEndpoint = (query: any) => createUrl('contracts', { query })
