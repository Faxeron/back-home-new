import { createUrl } from '@/@core/composable/createUrl'

export const estimateTemplateMaterialsEndpoint = (query: any) =>
  createUrl('estimate-templates/materials', { query })
export const estimateTemplateMaterialEndpoint = (id: number | string) =>
  createUrl(`estimate-templates/materials/${id}`)

export const estimateTemplateSeptiksEndpoint = (query: any) =>
  createUrl('estimate-templates/septiks', { query })
export const estimateTemplateSeptikEndpoint = (id: number | string) =>
  createUrl(`estimate-templates/septiks/${id}`)
