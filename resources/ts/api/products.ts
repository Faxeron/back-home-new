import { createUrl } from '@/@core/composable/createUrl'

export const productsEndpoint = (query: any) => createUrl('products', { query })
export const productCategoriesEndpoint = (query: any) => createUrl('products/categories', { query })
export const productSubcategoriesEndpoint = (query: any) => createUrl('products/subcategories', { query })
export const productBrandsEndpoint = (query: any) => createUrl('products/brands', { query })
export const productKindsEndpoint = (query: any) => createUrl('products/kinds', { query })
export const productEndpoint = (id: number | string) => createUrl(`products/${id}`)
