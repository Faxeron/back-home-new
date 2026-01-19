<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import ProductsTable from '@/modules/products/components/ProductsTable.vue'
import { useProductFilters } from '@/modules/products/composables/useProductFilters'
import { useProductLookups } from '@/modules/products/composables/useProductLookups'
import { useTableInfinite } from '@/composables/useTableLazy'
import type { Product } from '@/modules/products/types/products.types'

const router = useRouter()
const tableRef = ref<any>(null)
const scrollHeight = ref('700px')
const viewMode = ref<'table' | 'cards'>('cards')
const reloadRef = ref<() => void>(() => {})

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
  hasMore,
  loadMore,
  virtualScrollerOptions,
} = useTableInfinite<Product>({
  endpoint: 'products',
  perPage: 200,
  rowHeight: 52,
  params: () => serverParams.value,
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
  <ProductsTable
    ref="tableRef"
    v-model:search="search"
    v-model:categoryId="categoryId"
    v-model:subCategoryId="subCategoryId"
    v-model:brandId="brandId"
    v-model:viewMode="viewMode"
    :rows="data"
    :loading="loading"
    :totalRecords="totalRecords"
    :scrollHeight="scrollHeight"
    :virtualScrollerOptions="virtualScrollerOptions"
    :categories="categories"
    :subcategories="subcategories"
    :brands="brands"
    :hasMore="hasMore"
    :loadMore="loadMore"
    @sort="handleSort"
    @reset="resetFilters"
    @open="openProduct"
  />
</template>
