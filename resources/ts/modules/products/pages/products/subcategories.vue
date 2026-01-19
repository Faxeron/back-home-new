<script setup lang="ts">
import { productCategoriesEndpoint, productSubcategoriesEndpoint } from '@/modules/products/api/products.api'
import type { ProductCategory, ProductSubcategory } from '@/modules/products/types/products.types'
import {
  PRODUCT_SUBCATEGORY_HEADERS,
  PRODUCT_SUBCATEGORY_LABELS,
  formatDictionaryNumber,
} from '@/modules/products/config/productSubcategories.config'

const page = ref(1)
const itemsPerPage = ref(25)
const search = ref('')
const categoryId = ref<number | null>(null)

const headers = PRODUCT_SUBCATEGORY_HEADERS

const endpoint = productSubcategoriesEndpoint({ page, per_page: itemsPerPage, q: search, category_id: categoryId })
const { data: response, execute: fetchItems, isFetching } = await useApi<{ data: ProductSubcategory[]; meta: any }>(endpoint)

const rows = computed(() => response.value?.data ?? [])
const pagination = computed(() => response.value?.meta ?? { total: 0, per_page: itemsPerPage.value })

const categoriesEndpoint = productCategoriesEndpoint({ per_page: 200 })
const { data: categoriesResponse, execute: fetchCategories } = await useApi<{ data: ProductCategory[]; meta: any }>(categoriesEndpoint)
const categories = computed(() => categoriesResponse.value?.data ?? [])

watch([page, itemsPerPage], () => fetchItems())
watch([search, categoryId], () => {
  page.value = 1
  fetchItems()
})

onMounted(() => fetchCategories())

const formatNumber = formatDictionaryNumber
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardTitle>{{ PRODUCT_SUBCATEGORY_LABELS.title }}</VCardTitle>
        <VCardText>
          <VRow class="gap-y-4">
            <VCol
              cols="12"
              md="4"
            >
              <AppTextField
                v-model="search"
                :label="PRODUCT_SUBCATEGORY_LABELS.searchLabel"
                :placeholder="PRODUCT_SUBCATEGORY_LABELS.namePlaceholder"
                clearable
                density="comfortable"
              />
            </VCol>
            <VCol
              cols="12"
              md="4"
            >
              <AppSelect
                v-model="categoryId"
                :items="categories"
                item-title="name"
                item-value="id"
                :label="PRODUCT_SUBCATEGORY_LABELS.categoryLabel"
                clearable
              />
            </VCol>
          </VRow>
        </VCardText>

        <VDivider />

        <VCardText>
          <VDataTableServer
            v-model:page="page"
            v-model:items-per-page="itemsPerPage"
            :headers="headers"
            :items="rows"
            :items-length="pagination.total ?? 0"
            :loading="isFetching"
            :items-per-page-options="[10, 25, 50, 100]"
            item-value="id"
            class="text-no-wrap"
          >
            <template #item.category="{ item }">
              {{ item.category?.name ?? '—' }}
            </template>
            <template #item.products_count="{ item }">
              {{ formatNumber(item.products_count) }}
            </template>
          </VDataTableServer>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>
