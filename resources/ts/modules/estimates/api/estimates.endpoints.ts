import { createUrl } from '@/@core/composable/createUrl'

export const estimatesEndpoint = (query: any) => createUrl('estimates', { query })
export const estimateEndpoint = (id: number | string) => createUrl(`estimates/${id}`)
export const estimateApplyTemplateEndpoint = (id: number | string) =>
  createUrl(`estimates/${id}/apply-template`)
export const estimateItemsEndpoint = (estimateId: number | string) =>
  createUrl(`estimates/${estimateId}/items`)
export const estimateItemEndpoint = (estimateId: number | string, itemId: number | string) =>
  createUrl(`estimates/${estimateId}/items/${itemId}`)
