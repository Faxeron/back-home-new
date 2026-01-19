<script setup lang="ts">
import { computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Button from 'primevue/button'
import InputSwitch from 'primevue/inputswitch'
import type { Product, ProductBrand, ProductCategory, ProductSubcategory } from '@/modules/products/types/products.types'
import {
  PRODUCT_PRICE_TABLE_EMPTY_TEXT,
  PRODUCT_PRICE_TABLE_HEADERS,
  PRODUCT_PRICE_TABLE_LABELS,
} from '@/modules/products/config/productsPriceTable.config'

const props = defineProps<{
  rows: Product[]
  loading: boolean
  totalRecords: number
  scrollHeight: string
  virtualScrollerOptions: Record<string, any>
  search: string
  categoryId: number | null
  subCategoryId: number | null
  brandId: number | null
  categories: ProductCategory[]
  subcategories: ProductSubcategory[]
  brands: ProductBrand[]
  errorMessage?: string
}>()

const emit = defineEmits<{
  (e: 'update:search', value: string): void
  (e: 'update:categoryId', value: number | null): void
  (e: 'update:subCategoryId', value: number | null): void
  (e: 'update:brandId', value: number | null): void
  (e: 'reset'): void
  (e: 'sort', event: any): void
  (e: 'open', row: Product): void
  (e: 'update-field', payload: { row: Product; field: keyof Product; value: any }): void
  (e: 'update-flag', payload: { row: Product; field: 'is_visible' | 'is_top' | 'is_new'; value: boolean }): void
}>()

const searchModel = computed({
  get: () => props.search,
  set: value => emit('update:search', value),
})

const categoryModel = computed({
  get: () => props.categoryId,
  set: value => emit('update:categoryId', value),
})

const subCategoryModel = computed({
  get: () => props.subCategoryId,
  set: value => emit('update:subCategoryId', value),
})

const brandModel = computed({
  get: () => props.brandId,
  set: value => emit('update:brandId', value),
})

const totalLabel = computed(() => Number(props.totalRecords ?? 0).toLocaleString('ru-RU'))
const hasFilters = computed(() => !!props.search || props.categoryId !== null || props.subCategoryId !== null || props.brandId !== null)

const updateNumberField = (row: Product, field: keyof Product) => {
  emit('update-field', { row, field, value: row[field] })
}
</script>

<template>
  <DataTable
    :value="rows"
    dataKey="id"
    class="p-datatable-sm"
    :loading="loading"
    :totalRecords="totalRecords"
    :tableStyle="{ tableLayout: 'fixed', minWidth: '1600px' }"
    scrollable
    :scrollHeight="scrollHeight"
    :virtualScrollerOptions="virtualScrollerOptions"
    lazy
    stripedRows
    @sort="emit('sort', $event)"
  >
    <template #header>
      <div class="flex flex-column gap-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-2">
            <InputText
              v-model="searchModel"
              class="w-64"
              :placeholder="PRODUCT_PRICE_TABLE_LABELS.searchPlaceholder"
            />
            <Button
              v-if="searchModel"
              icon="pi pi-times"
              text
              :aria-label="PRODUCT_PRICE_TABLE_LABELS.clearSearchAria"
              @click="emit('update:search', '')"
            />
            <Select
              v-model="brandModel"
              :options="brands"
              optionLabel="name"
              optionValue="id"
              :placeholder="PRODUCT_PRICE_TABLE_LABELS.brandPlaceholder"
              class="w-48"
              showClear
            />
            <Select
              v-model="categoryModel"
              :options="categories"
              optionLabel="name"
              optionValue="id"
              :placeholder="PRODUCT_PRICE_TABLE_LABELS.categoryPlaceholder"
              class="w-48"
              showClear
            />
            <Select
              v-model="subCategoryModel"
              :options="subcategories"
              optionLabel="name"
              optionValue="id"
              :placeholder="PRODUCT_PRICE_TABLE_LABELS.subCategoryPlaceholder"
              class="w-48"
              showClear
              :disabled="!categoryModel"
            />
            <Button
              :label="PRODUCT_PRICE_TABLE_LABELS.resetFilters"
              size="small"
              text
              icon="pi pi-refresh"
              :disabled="!hasFilters"
              @click="emit('reset')"
            />
          </div>
          <TableTotalLabel :label="PRODUCT_PRICE_TABLE_LABELS.total" :value="totalLabel" />
        </div>
        <div
          v-if="errorMessage"
          class="text-sm"
          style="color: #dc2626"
        >
          {{ errorMessage }}
        </div>
      </div>
    </template>

    <Column
      field="id"
      :header="PRODUCT_PRICE_TABLE_HEADERS.id"
      sortable
      style="inline-size: 6ch;"
    >
      <template #body="{ data }">
        {{ data.id ?? PRODUCT_PRICE_TABLE_EMPTY_TEXT }}
      </template>
    </Column>

    <Column
      field="scu"
      :header="PRODUCT_PRICE_TABLE_HEADERS.scu"
      sortable
      style="inline-size: 12ch;"
    >
      <template #body="{ data }">
        {{ data.scu ?? PRODUCT_PRICE_TABLE_EMPTY_TEXT }}
      </template>
    </Column>

    <Column
      field="flags"
      :header="PRODUCT_PRICE_TABLE_HEADERS.flags"
      style="inline-size: 16ch;"
    >
      <template #body="{ data }">
        <div class="flex items-center gap-2">
          <InputSwitch
            v-model="data.is_visible"
            @update:modelValue="emit('update-flag', { row: data, field: 'is_visible', value: $event })"
          />
          <InputSwitch
            v-model="data.is_top"
            @update:modelValue="emit('update-flag', { row: data, field: 'is_top', value: $event })"
          />
          <InputSwitch
            v-model="data.is_new"
            @update:modelValue="emit('update-flag', { row: data, field: 'is_new', value: $event })"
          />
        </div>
      </template>
    </Column>

    <Column
      field="category"
      :header="PRODUCT_PRICE_TABLE_HEADERS.category"
      style="inline-size: 18ch;"
    >
      <template #body="{ data }">
        {{ data.category?.name ?? PRODUCT_PRICE_TABLE_EMPTY_TEXT }}
      </template>
    </Column>

    <Column
      field="sub_category"
      :header="PRODUCT_PRICE_TABLE_HEADERS.subCategory"
      style="inline-size: 18ch;"
    >
      <template #body="{ data }">
        {{ data.sub_category?.name ?? PRODUCT_PRICE_TABLE_EMPTY_TEXT }}
      </template>
    </Column>

    <Column
      field="brand"
      :header="PRODUCT_PRICE_TABLE_HEADERS.brand"
      style="inline-size: 16ch;"
    >
      <template #body="{ data }">
        {{ data.brand?.name ?? PRODUCT_PRICE_TABLE_EMPTY_TEXT }}
      </template>
    </Column>

    <Column
      field="name"
      :header="PRODUCT_PRICE_TABLE_HEADERS.name"
      style="width: 52ch;"
      :headerStyle="{ width: '52ch', minWidth: '52ch', maxWidth: '52ch' }"
      :bodyStyle="{ width: '52ch', minWidth: '52ch', maxWidth: '52ch' }"
    >
      <template #body="{ data }">
        <div class="leading-tight py-1">
          <div class="font-medium">{{ data.name ?? PRODUCT_PRICE_TABLE_EMPTY_TEXT }}</div>
          <div class="text-xs text-muted">SKU: {{ data.scu ?? PRODUCT_PRICE_TABLE_EMPTY_TEXT }}</div>
        </div>
      </template>
    </Column>

    <Column
      field="price"
      :header="PRODUCT_PRICE_TABLE_HEADERS.price"
      style="width: 9ch;"
      :headerStyle="{ width: '9ch', minWidth: '9ch', maxWidth: '9ch' }"
      :bodyStyle="{ width: '9ch', minWidth: '9ch', maxWidth: '9ch' }"
    >
      <template #body="{ data }">
        <InputNumber
          v-model="data.price"
          class="w-full price-input"
          :disabled="data.is_global"
          @blur="updateNumberField(data, 'price')"
        />
      </template>
    </Column>

    <Column
      field="price_sale"
      :header="PRODUCT_PRICE_TABLE_HEADERS.priceSale"
      style="width: 9ch;"
      :headerStyle="{ width: '9ch', minWidth: '9ch', maxWidth: '9ch' }"
      :bodyStyle="{ width: '9ch', minWidth: '9ch', maxWidth: '9ch' }"
    >
      <template #body="{ data }">
        <InputNumber
          v-model="data.price_sale"
          class="w-full price-input"
          :disabled="data.is_global"
          @blur="updateNumberField(data, 'price_sale')"
        />
      </template>
    </Column>

    <Column
      field="price_vendor"
      :header="PRODUCT_PRICE_TABLE_HEADERS.priceVendor"
      style="width: 10ch;"
      :headerStyle="{ width: '10ch', minWidth: '10ch', maxWidth: '10ch' }"
      :bodyStyle="{ width: '10ch', minWidth: '10ch', maxWidth: '10ch' }"
    >
      <template #body="{ data }">
        <InputNumber
          v-model="data.price_vendor"
          class="w-full price-input"
          :disabled="data.is_global"
          @blur="updateNumberField(data, 'price_vendor')"
        />
      </template>
    </Column>

    <Column
      field="price_vendor_min"
      :header="PRODUCT_PRICE_TABLE_HEADERS.priceVendorMin"
      style="width: 10ch;"
      :headerStyle="{ width: '10ch', minWidth: '10ch', maxWidth: '10ch' }"
      :bodyStyle="{ width: '10ch', minWidth: '10ch', maxWidth: '10ch' }"
    >
      <template #body="{ data }">
        <InputNumber
          v-model="data.price_vendor_min"
          class="w-full price-input"
          :disabled="data.is_global"
          @blur="updateNumberField(data, 'price_vendor_min')"
        />
      </template>
    </Column>

    <Column
      field="price_zakup"
      :header="PRODUCT_PRICE_TABLE_HEADERS.priceZakup"
      style="width: 9ch;"
      :headerStyle="{ width: '9ch', minWidth: '9ch', maxWidth: '9ch' }"
      :bodyStyle="{ width: '9ch', minWidth: '9ch', maxWidth: '9ch' }"
    >
      <template #body="{ data }">
        <InputNumber
          v-model="data.price_zakup"
          class="w-full price-input"
          :disabled="data.is_global"
          @blur="updateNumberField(data, 'price_zakup')"
        />
      </template>
    </Column>

    <Column
      field="price_delivery"
      :header="PRODUCT_PRICE_TABLE_HEADERS.priceDelivery"
      style="width: 9ch;"
      :headerStyle="{ width: '9ch', minWidth: '9ch', maxWidth: '9ch' }"
      :bodyStyle="{ width: '9ch', minWidth: '9ch', maxWidth: '9ch' }"
    >
      <template #body="{ data }">
        <InputNumber
          v-model="data.price_delivery"
          class="w-full price-input"
          :disabled="data.is_global"
          @blur="updateNumberField(data, 'price_delivery')"
        />
      </template>
    </Column>

    <Column
      field="montaj"
      :header="PRODUCT_PRICE_TABLE_HEADERS.montaj"
      style="width: 9ch;"
      :headerStyle="{ width: '9ch', minWidth: '9ch', maxWidth: '9ch' }"
      :bodyStyle="{ width: '9ch', minWidth: '9ch', maxWidth: '9ch' }"
    >
      <template #body="{ data }">
        <InputNumber
          v-model="data.montaj"
          class="w-full price-input"
          :disabled="data.is_global"
          @blur="updateNumberField(data, 'montaj')"
        />
      </template>
    </Column>

    <Column
      field="montaj_sebest"
      :header="PRODUCT_PRICE_TABLE_HEADERS.montajSebest"
      style="width: 10ch;"
      :headerStyle="{ width: '10ch', minWidth: '10ch', maxWidth: '10ch' }"
      :bodyStyle="{ width: '10ch', minWidth: '10ch', maxWidth: '10ch' }"
    >
      <template #body="{ data }">
        <InputNumber
          v-model="data.montaj_sebest"
          class="w-full price-input"
          :disabled="data.is_global"
          @blur="updateNumberField(data, 'montaj_sebest')"
        />
      </template>
    </Column>

    <Column
      field="actions"
      header=""
      style="inline-size: 6ch;"
    >
      <template #body="{ data }">
        <Button
          icon="pi pi-external-link"
          text
          :aria-label="PRODUCT_PRICE_TABLE_LABELS.openCardAria"
          @click="emit('open', data)"
        />
      </template>
    </Column>

    <template #empty>
      <div class="text-center py-6 text-muted">{{ PRODUCT_PRICE_TABLE_LABELS.empty }}</div>
    </template>

    <template #loading>
      <div class="text-center py-6 text-muted">{{ PRODUCT_PRICE_TABLE_LABELS.loading }}</div>
    </template>
  </DataTable>
</template>

<style scoped>
:deep(.price-input) {
  min-width: 0;
  width: 100%;
}

:deep(.price-input .p-inputnumber-input) {
  min-width: 0;
  width: 100%;
  box-sizing: border-box;
  font-size: 0.85rem;
  padding-inline: 0.35rem;
}
</style>

