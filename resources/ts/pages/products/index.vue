<script setup lang="ts">
import { productsEndpoint, productBrandsEndpoint, productCategoriesEndpoint, productSubcategoriesEndpoint } from '@/api/products'
import type { Product, ProductBrand, ProductCategory, ProductSubcategory } from '@/types/products'

const page = ref(1)
const itemsPerPage = ref(25)
const search = ref('')
const categoryId = ref<number | null>(null)
const subCategoryId = ref<number | null>(null)
const brandId = ref<number | null>(null)

const headers = [
  { title: 'Товар', key: 'name', minWidth: 220 },
  { title: 'Категория', key: 'category' },
  { title: 'Подкатегория', key: 'sub_category' },
  { title: 'Бренд', key: 'brand' },
  { title: 'SKU', key: 'scu' },
  { title: 'Цена', key: 'price', align: 'end' },
  { title: 'Цена продажи', key: 'price_sale', align: 'end' },
]

const endpoint = productsEndpoint({
  page,
  per_page: itemsPerPage,
  q: search,
  category_id: categoryId,
  sub_category_id: subCategoryId,
  brand_id: brandId,
})

const { data: response, execute: fetchProducts, isFetching } = await useApi<{ data: Product[]; meta: any }>(endpoint)

const rows = computed(() => response.value?.data ?? [])
const pagination = computed(() => response.value?.meta ?? { total: 0, per_page: itemsPerPage.value })

const categoriesEndpoint = productCategoriesEndpoint({ per_page: 200 })
const { data: categoriesResponse, execute: fetchCategories } = await useApi<{ data: ProductCategory[]; meta: any }>(categoriesEndpoint)
const categories = computed(() => categoriesResponse.value?.data ?? [])

const brandsEndpoint = productBrandsEndpoint({ per_page: 200 })
const { data: brandsResponse, execute: fetchBrands } = await useApi<{ data: ProductBrand[]; meta: any }>(brandsEndpoint)
const brands = computed(() => brandsResponse.value?.data ?? [])

const subcategoriesEndpoint = productSubcategoriesEndpoint({ per_page: 200, category_id: categoryId })
const { data: subcategoriesResponse, execute: fetchSubcategories, isFetching: isSubcategoriesLoading } = await useApi<{ data: ProductSubcategory[]; meta: any }>(subcategoriesEndpoint)
const subcategories = computed(() => subcategoriesResponse.value?.data ?? [])

watch([page, itemsPerPage], () => fetchProducts())

watch([search, categoryId, subCategoryId, brandId], () => {
  page.value = 1
  fetchProducts()
})

watch(categoryId, () => {
  subCategoryId.value = null
  fetchSubcategories()
})

onMounted(() => {
  fetchCategories()
  fetchBrands()
  fetchSubcategories()
})

const formatMoney = (value?: number) => new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 2 }).format(Number(value ?? 0))
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardTitle>Товары</VCardTitle>
        <VCardText>
          <VRow class="gap-y-4">
            <VCol
              cols="12"
              md="3"
            >
              <AppTextField
                v-model="search"
                label="Поиск"
                placeholder="Название или SKU"
                density="comfortable"
                clearable
              />
            </VCol>
            <VCol
              cols="12"
              md="3"
            >
              <AppSelect
                v-model="brandId"
                :items="brands"
                item-title="name"
                item-value="id"
                label="Бренд"
                clearable
              />
            </VCol>
            <VCol
              cols="12"
              md="3"
            >
              <AppSelect
                v-model="categoryId"
                :items="categories"
                item-title="name"
                item-value="id"
                label="Категория"
                clearable
              />
            </VCol>
            <VCol
              cols="12"
              md="3"
            >
              <AppSelect
                v-model="subCategoryId"
                :items="subcategories"
                item-title="name"
                item-value="id"
                label="Подкатегория"
                :loading="isSubcategoriesLoading"
                :disabled="!categoryId"
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
            <template #item.name="{ item }">
              <div class="d-flex flex-column">
                <span class="text-high-emphasis font-weight-medium">{{ item.name }}</span>
                <span
                  v-if="item.scu"
                  class="text-caption text-disabled"
                >
                  SKU: {{ item.scu }}
                </span>
              </div>
            </template>
            <template #item.category="{ item }">
              {{ item.category?.name ?? '—' }}
            </template>
            <template #item.sub_category="{ item }">
              {{ item.sub_category?.name ?? '—' }}
            </template>
            <template #item.brand="{ item }">
              {{ item.brand?.name ?? '—' }}
            </template>
            <template #item.price="{ item }">
              {{ formatMoney(item.price) }}
            </template>
            <template #item.price_sale="{ item }">
              {{ formatMoney(item.price_sale ?? item.price) }}
            </template>
          </VDataTableServer>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>
