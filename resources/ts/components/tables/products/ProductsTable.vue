<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Card from 'primevue/card'
import type { Product, ProductBrand, ProductCategory, ProductSubcategory } from '@/types/products'

const EMPTY_TEXT = '\u2014'

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
  viewMode: 'table' | 'cards'
  hasMore?: boolean
  loadMore?: () => void
}>()

const emit = defineEmits<{
  (e: 'update:search', value: string): void
  (e: 'update:categoryId', value: number | null): void
  (e: 'update:subCategoryId', value: number | null): void
  (e: 'update:brandId', value: number | null): void
  (e: 'update:viewMode', value: 'table' | 'cards'): void
  (e: 'reset'): void
  (e: 'sort', event: any): void
  (e: 'open', row: Product): void
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

const viewModeModel = computed({
  get: () => props.viewMode,
  set: value => emit('update:viewMode', value),
})

const totalLabel = computed(() => Number(props.totalRecords ?? 0).toLocaleString('ru-RU'))
const hasFilters = computed(() => !!props.search || props.categoryId !== null || props.subCategoryId !== null || props.brandId !== null)
const sentinel = ref<HTMLElement | null>(null)
let observer: IntersectionObserver | null = null

const canAutoLoad = computed(() => !!props.hasMore && !!props.loadMore && !props.loading)

const setupObserver = () => {
  if (observer || typeof window === 'undefined' || !('IntersectionObserver' in window)) return

  observer = new IntersectionObserver(
    entries => {
      const isVisible = entries.some(entry => entry.isIntersecting)
      if (isVisible && canAutoLoad.value) props.loadMore?.()
    },
    { root: null, rootMargin: '200px', threshold: 0.1 },
  )
}

const observeSentinel = (element: HTMLElement | null) => {
  if (!observer) setupObserver()
  if (!observer || !element) return
  observer.observe(element)
}

const unobserveSentinel = (element: HTMLElement | null) => {
  if (!observer || !element) return
  observer.unobserve(element)
}

watch(
  () => sentinel.value,
  (next, prev) => {
    if (prev) unobserveSentinel(prev)
    if (next && viewModeModel.value === 'cards') observeSentinel(next)
  },
)

watch(
  () => viewModeModel.value,
  mode => {
    if (mode === 'cards' && sentinel.value) observeSentinel(sentinel.value)
    if (mode !== 'cards' && sentinel.value) unobserveSentinel(sentinel.value)
  },
)

onMounted(() => {
  setupObserver()
  if (viewModeModel.value === 'cards') observeSentinel(sentinel.value)
})

onBeforeUnmount(() => {
  if (observer) observer.disconnect()
  observer = null
})

const formatDate = (value?: string) => {
  if (!value) return EMPTY_TEXT
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? EMPTY_TEXT : date.toLocaleDateString('ru-RU')
}
</script>

<template>
  <div class="flex flex-column gap-4">
    <DataTable
      v-if="viewModeModel === 'table'"
      :value="rows"
      dataKey="id"
      class="p-datatable-sm"
      :loading="loading"
      :totalRecords="totalRecords"
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
                placeholder="Поиск по названию или SKU"
              />
              <Button
                v-if="searchModel"
                icon="pi pi-times"
                text
                aria-label="Сбросить поиск"
                @click="emit('update:search', '')"
              />
              <Select
                v-model="brandModel"
                :options="brands"
                optionLabel="name"
                optionValue="id"
                placeholder="Бренд"
                class="w-48"
                showClear
              />
              <Select
                v-model="categoryModel"
                :options="categories"
                optionLabel="name"
                optionValue="id"
                placeholder="Категория"
                class="w-48"
                showClear
              />
              <Select
                v-model="subCategoryModel"
                :options="subcategories"
                optionLabel="name"
                optionValue="id"
                placeholder="Подкатегория"
                class="w-48"
                showClear
                :disabled="!categoryModel"
              />
              <Button
                label="Сбросить фильтры"
                size="small"
                text
                icon="pi pi-refresh"
                :disabled="!hasFilters"
                @click="emit('reset')"
              />
            </div>
            <div class="flex items-center gap-2">
              <span class="text-sm text-muted">Всего: {{ totalLabel }}</span>
              <div class="flex items-center gap-1">
                <Button
                  icon="pi pi-list"
                  text
                  :outlined="viewModeModel !== 'table'"
                  aria-label="Таблица"
                  @click="viewModeModel = 'table'"
                />
                <Button
                  icon="pi pi-th-large"
                  text
                  :outlined="viewModeModel !== 'cards'"
                  aria-label="Карточки"
                  @click="viewModeModel = 'cards'"
                />
              </div>
            </div>
          </div>
        </div>
      </template>

      <Column
        field="id"
        header="ID"
        sortable
        style="inline-size: 6ch;"
      >
        <template #body="{ data }">
          {{ data.id ?? EMPTY_TEXT }}
        </template>
      </Column>

      <Column
        field="name"
        header="Название"
        sortable
      >
        <template #body="{ data }">
          <div class="leading-tight py-1">
            <div class="font-medium">{{ data.name ?? EMPTY_TEXT }}</div>
            <div class="text-xs text-muted">SKU: {{ data.scu ?? EMPTY_TEXT }}</div>
          </div>
        </template>
      </Column>

      <Column
        field="scu"
        header="SCU"
        sortable
        style="inline-size: 12ch;"
      >
        <template #body="{ data }">
          {{ data.scu ?? EMPTY_TEXT }}
        </template>
      </Column>

      <Column
        field="kind"
        header="Вид"
        style="inline-size: 14ch;"
      >
        <template #body="{ data }">
          {{ data.kind?.name ?? EMPTY_TEXT }}
        </template>
      </Column>

      <Column
        field="category"
        header="Категория"
      >
        <template #body="{ data }">
          {{ data.category?.name ?? EMPTY_TEXT }}
        </template>
      </Column>

      <Column
        field="brand"
        header="Бренд"
        style="inline-size: 16ch;"
      >
        <template #body="{ data }">
          {{ data.brand?.name ?? EMPTY_TEXT }}
        </template>
      </Column>

      <Column
        field="is_visible"
        header="Видимость"
        style="inline-size: 12ch;"
      >
        <template #body="{ data }">
          <i
            class="pi"
            :class="data.is_visible ? 'pi-eye' : 'pi-eye-slash'"
            :style="{ color: data.is_visible ? '#16a34a' : '#94a3b8' }"
          />
        </template>
      </Column>

      <Column
        field="updated_at"
        header="Обновлено"
        sortable
        style="inline-size: 12ch;"
      >
        <template #body="{ data }">
          {{ formatDate(data.updated_at as string) }}
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
            aria-label="Открыть карточку"
            @click="emit('open', data)"
          />
        </template>
      </Column>

      <template #empty>
        <div class="text-center py-6 text-muted">Нет товаров.</div>
      </template>

      <template #loading>
        <div class="text-center py-6 text-muted">Загрузка...</div>
      </template>
    </DataTable>

    <div
      v-else
      class="flex flex-column gap-3"
    >
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
          <InputText
            v-model="searchModel"
            class="w-64"
            placeholder="Поиск по названию или SKU"
          />
          <Button
            v-if="searchModel"
            icon="pi pi-times"
            text
            aria-label="Сбросить поиск"
            @click="emit('update:search', '')"
          />
          <Select
            v-model="brandModel"
            :options="brands"
            optionLabel="name"
            optionValue="id"
            placeholder="Бренд"
            class="w-48"
            showClear
          />
          <Select
            v-model="categoryModel"
            :options="categories"
            optionLabel="name"
            optionValue="id"
            placeholder="Категория"
            class="w-48"
            showClear
          />
          <Select
            v-model="subCategoryModel"
            :options="subcategories"
            optionLabel="name"
            optionValue="id"
            placeholder="Подкатегория"
            class="w-48"
            showClear
            :disabled="!categoryModel"
          />
          <Button
            label="Сбросить фильтры"
            size="small"
            text
            icon="pi pi-refresh"
            :disabled="!hasFilters"
            @click="emit('reset')"
          />
        </div>
        <div class="flex items-center gap-2">
          <span class="text-sm text-muted">Всего: {{ totalLabel }}</span>
          <div class="flex items-center gap-1">
            <Button
              icon="pi pi-list"
              text
              :outlined="viewModeModel !== 'table'"
              aria-label="Таблица"
              @click="viewModeModel = 'table'"
            />
            <Button
              icon="pi pi-th-large"
              text
              :outlined="viewModeModel !== 'cards'"
              aria-label="Карточки"
              @click="viewModeModel = 'cards'"
            />
          </div>
        </div>
      </div>

      <div class="grid">
        <div
          v-for="item in rows"
          :key="item.id"
          class="col-12 md:col-6 lg:col-4"
        >
          <div
            class="cursor-pointer h-full"
            @click="emit('open', item)"
          >
            <Card class="h-full">
              <template #title>
                <div class="flex items-start justify-between gap-2">
                  <div class="text-base font-medium leading-tight">
                    {{ item.name ?? EMPTY_TEXT }}
                  </div>
                  <i
                    class="pi"
                    :class="item.is_visible ? 'pi-eye' : 'pi-eye-slash'"
                    :style="{ color: item.is_visible ? '#16a34a' : '#94a3b8' }"
                  />
                </div>
              </template>
              <template #content>
                <div class="flex flex-column gap-2 text-sm">
                  <div class="text-muted">SKU: {{ item.scu ?? EMPTY_TEXT }}</div>
                  <div>{{ item.kind?.name ?? EMPTY_TEXT }} / {{ item.category?.name ?? EMPTY_TEXT }}</div>
                  <div class="text-xs text-muted">Бренд: {{ item.brand?.name ?? EMPTY_TEXT }}</div>
                </div>
              </template>
              <template #footer>
                <div class="flex justify-end">
                  <Button
                    icon="pi pi-external-link"
                    text
                    aria-label="Открыть карточку"
                    @click.stop="emit('open', item)"
                  />
                </div>
              </template>
            </Card>
          </div>
        </div>
      </div>
      <div ref="sentinel" class="h-2 w-full" />
      <div
        v-if="loading"
        class="text-center py-6 text-muted"
      >
        Загрузка...
      </div>
    </div>
  </div>
</template>
