import { createUrl } from '@/@core/composable/createUrl'

export const cashBoxesEndpoint = (query: any) => createUrl('settings/cash-boxes', { query })
export const companiesEndpoint = (query: any) => createUrl('settings/companies', { query })
export const spendingFundsEndpoint = (query: any) => createUrl('settings/spending-funds', { query })
export const spendingItemsEndpoint = (query: any) => createUrl('settings/spending-items', { query })
export const contractStatusesEndpoint = (query: any) => createUrl('settings/contract-statuses', { query })
export const saleTypesEndpoint = (query: any) => createUrl('settings/sale-types', { query })
export const citiesEndpoint = (query: any) => createUrl('settings/cities', { query })
export const districtsEndpoint = (query: any) => createUrl('settings/cities-districts', { query })
export const tenantsEndpoint = (query: any) => createUrl('settings/tenants', { query })
