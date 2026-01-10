<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import ProductsPriceTable from '@/components/tables/products/ProductsPriceTable.vue'
import { useProductFilters } from '@/composables/useProductFilters'
import { useProductLookups } from '@/composables/useProductLookups'
import { useTableInfinite } from '@/composables/useTableLazy'
import { $api } from '@/utils/api'
import type { Product } from '@/types/products'

const router = useRouter()
const tableRef = ref<any>(null)
const scrollHeight = ref('700px')
const reloadRef = ref<() => void>(() => {})
const errorMessage = ref('')
const saving = reactive<Record<number, boolean>>({})

const {
  search,
  categoryId,
  subCategoryId,
  brandId,
  serverParams,
  resetFilters,
  handleSort,
} = useProductFilters({
  onChange: () => reloadRef.value(),
})

const {
  categories,
  subcategories,
  brands,
  loadCategories,
  loadBrands,
  loadSubcategories,
} = useProductLookups()

const {
  data,
  total: totalRecords,
  loading,
  reset: resetData,
  virtualScrollerOptions,
} = useTableInfinite<Product>({
  endpoint: 'products',
  perPage: 200,
  rowHeight: 52,
  params: () => ({ ...serverParams.value, include_global: false }),
})

reloadRef.value = () => {
  resetData()
}

const updateScrollHeight = () => {
  const tableEl = tableRef.value?.$el as HTMLElement | undefined
  if (!tableEl) return
  const rect = tableEl.getBoundingClientRect()
  const padding = 24
  const nextHeight = Math.max(320, window.innerHeight - rect.top - padding)
  scrollHeight.value = `${Math.floor(nextHeight)}px`
}

const handleResize = () => {
  updateScrollHeight()
}

watch(categoryId, async () => {
  subCategoryId.value = null
  await loadSubcategories(categoryId.value)
})

const openProduct = (row: Product) => {
  router.push({ path: `/products/${row.id}` })
}

const normalizeNumber = (value: any) => {
  if (value === '' || value === null || value === undefined) return null
  const numberValue = Number(String(value).replace(',', '.'))
  return Number.isNaN(numberValue) ? null : numberValue
}

const updateProduct = async (row: Product, payload: Record<string, any>) => {
  if (saving[row.id]) return
  saving[row.id] = true
  errorMessage.value = ''

  try {
    await $api(`products/${row.id}`, {
      method: 'PATCH',
      body: payload,
    })
    return true
  } catch (error) {
    console.error(error)
    errorMessage.value = 'Ќе удалось сохранить изменени€'
    return false
  } finally {
    saving[row.id] = false
  }
}

const numericFields = new Set([
  'price',
  'price_sale',
  'price_vendor',
  'price_vendor_min',
  'price_zakup',
  'price_delivery',
  'montaj',
  'montaj_sebest',
])

const handleUpdateField = async (payload: { row: Product; field: keyof Product; value: any }) => {
  const { row, field, value } = payload
  if (row.is_global && numericFields.has(String(field))) {
    errorMessage.value = '√лобальные товары нельз€ редактировать в прайсе'
    return
  }
  const previousValue = row[field]
  const nextValue = numericFields.has(String(field)) ? normalizeNumber(value) : value
  row[field] = nextValue as any
  const ok = await updateProduct(row, { [field]: nextValue })
  if (!ok)
    row[field] = previousValue as any
}

const handleUpdateFlag = async (payload: { row: Product; field: 'is_visible' | 'is_top' | 'is_new'; value: boolean }) => {
  const { row, field, value } = payload
  if (row.is_global && numericFields.has(String(field))) {
    errorMessage.value = '√лобальные товары нельз€ редактировать в прайсе'
    return
  }
  const previousValue = row[field]
  row[field] = value
  const ok = await updateProduct(row, { [field]: value })
  if (!ok)
    row[field] = previousValue
}

onMounted(async () => {
  await Promise.all([loadCategories(), loadBrands(), loadSubcategories(categoryId.value)])
  await resetData()
  await nextTick()
  updateScrollHeight()
  window.addEventListener('resize', handleResize)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
})
</script>

<template>
  <ProductsPriceTable
    ref="tableRef"
    v-model:search="search"
    v-model:categoryId="categoryId"
    v-model:subCategoryId="subCategoryId"
    v-model:brandId="brandId"
    :rows="data"
    :loading="loading"
    :totalRecords="totalRecords"
    :scrollHeight="scrollHeight"
    :virtualScrollerOptions="virtualScrollerOptions"
    :categories="categories"
    :subcategories="subcategories"
    :brands="brands"
    :errorMessage="errorMessage"
    @sort="handleSort"
    @reset="resetFilters"
    @open="openProduct"
    @update-field="handleUpdateField"
    @update-flag="handleUpdateFlag"
  />
</template>




