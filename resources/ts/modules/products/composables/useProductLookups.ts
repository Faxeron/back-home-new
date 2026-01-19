import { ref } from 'vue'
import { $api } from '@/utils/api'
import type { ProductBrand, ProductCategory, ProductSubcategory } from '@/modules/products/types/products.types'

const extractList = <T>(response: any): T[] => {
  if (Array.isArray(response?.data?.data)) return response.data.data
  if (Array.isArray(response?.data)) return response.data
  if (Array.isArray(response)) return response
  return []
}

export const useProductLookups = () => {
  const categories = ref<ProductCategory[]>([])
  const subcategories = ref<ProductSubcategory[]>([])
  const brands = ref<ProductBrand[]>([])

  const loadCategories = async () => {
    try {
      const response: any = await $api('products/categories', { params: { per_page: 200 } })
      categories.value = extractList<ProductCategory>(response)
    } catch (error) {
      console.error('Failed to load product categories', error)
      categories.value = []
    }
  }

  const loadBrands = async () => {
    try {
      const response: any = await $api('products/brands', { params: { per_page: 200 } })
      brands.value = extractList<ProductBrand>(response)
    } catch (error) {
      console.error('Failed to load product brands', error)
      brands.value = []
    }
  }

  const loadSubcategories = async (categoryId?: number | null) => {
    try {
      const params: Record<string, any> = { per_page: 200 }
      if (categoryId) params.category_id = categoryId
      const response: any = await $api('products/subcategories', { params })
      subcategories.value = extractList<ProductSubcategory>(response)
    } catch (error) {
      console.error('Failed to load product subcategories', error)
      subcategories.value = []
    }
  }

  return {
    categories,
    subcategories,
    brands,
    loadCategories,
    loadBrands,
    loadSubcategories,
  }
}
