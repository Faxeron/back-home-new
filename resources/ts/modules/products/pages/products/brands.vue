<script setup lang="ts">
import { productBrandsEndpoint } from '@/modules/products/api/products.api'
import type { ProductBrand } from '@/modules/products/types/products.types'
import {
  PRODUCT_BRAND_HEADERS,
  PRODUCT_BRAND_LABELS,
  formatDictionaryNumber,
} from '@/modules/products/config/productBrands.config'

const page = ref(1)
const itemsPerPage = ref(25)
const search = ref('')

const headers = PRODUCT_BRAND_HEADERS

const endpoint = productBrandsEndpoint({ page, per_page: itemsPerPage, q: search })
const { data: response, execute: fetchItems, isFetching } = await useApi<{ data: ProductBrand[]; meta: any }>(endpoint)

const rows = computed(() => response.value?.data ?? [])
const pagination = computed(() => response.value?.meta ?? { total: 0, per_page: itemsPerPage.value })

watch([page, itemsPerPage], () => fetchItems())
watch(search, () => {
  page.value = 1
  fetchItems()
})

const formatNumber = formatDictionaryNumber
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardTitle>{{ PRODUCT_BRAND_LABELS.title }}</VCardTitle>
        <VCardText>
          <AppTextField
            v-model="search"
            :label="PRODUCT_BRAND_LABELS.searchLabel"
            :placeholder="PRODUCT_BRAND_LABELS.namePlaceholder"
            clearable
            density="comfortable"
            style="max-inline-size: 320px;"
          />
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
            <template #item.products_count="{ item }">
              {{ formatNumber(item.products_count) }}
            </template>
          </VDataTableServer>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>
