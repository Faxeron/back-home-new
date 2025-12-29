<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { productEndpoint } from '@/api/products'
import type { Product } from '@/types/products'

const EMPTY_TEXT = '\u2014'

const route = useRoute()
const router = useRouter()
const activeTab = ref(0)

const productId = computed(() => {
  const raw = route.params.id
  return Array.isArray(raw) ? raw[0] : raw
})

const endpoint = computed(() => productEndpoint(productId.value ?? '').value)
const { data, error, isFetching, execute: fetchProduct } = await useApi<{ data: Product }>(endpoint)

watch(productId, () => fetchProduct())

const product = computed(() => data.value?.data)

const formatValue = (value: any) => {
  if (value === null || value === undefined || value === '') return EMPTY_TEXT
  return value
}

const formatDate = (value?: string) => {
  if (!value) return EMPTY_TEXT
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? EMPTY_TEXT : date.toLocaleDateString('ru-RU')
}

const formatPrice = (value?: number | null) => {
  if (value === null || value === undefined) return EMPTY_TEXT
  return new Intl.NumberFormat('ru-RU').format(value)
}

const basicRows = computed(() => [
  { label: 'SCU', value: formatValue(product.value?.scu) },
  { label: 'Вид товара', value: formatValue(product.value?.kind?.name) },
  { label: 'Категория', value: formatValue(product.value?.category?.name) },
  { label: 'Подкатегория', value: formatValue(product.value?.sub_category?.name) },
  { label: 'Бренд', value: formatValue(product.value?.brand?.name) },
  { label: 'Тип товара (product_type_id)', value: formatValue(product.value?.product_type_id) },
  { label: 'Ед. изм. (unit_id)', value: formatValue(product.value?.unit_id) },
  { label: 'Обновлено', value: formatDate(product.value?.updated_at as string) },
])

const priceRows = computed(() => [
  { label: 'Цена', value: formatPrice(product.value?.price ?? null) },
  { label: 'Цена по акции', value: formatPrice(product.value?.price_sale ?? null) },
  { label: 'Цена производителя', value: formatPrice(product.value?.price_vendor ?? null) },
  { label: 'Мин. цена производителя', value: formatPrice(product.value?.price_vendor_min ?? null) },
  { label: 'Цена закуп', value: formatPrice(product.value?.price_zakup ?? null) },
  { label: 'Доставка', value: formatPrice(product.value?.price_delivery ?? null) },
  { label: 'Монтаж', value: formatPrice(product.value?.montaj ?? null) },
  { label: 'Монтаж с/с', value: formatPrice(product.value?.montaj_sebest ?? null) },
])

const descriptionRows = computed(() => [
  { label: 'Короткое описание', value: formatValue(product.value?.description?.description_short) },
  { label: 'Длинное описание', value: formatValue(product.value?.description?.description_long) },
  { label: 'Достоинства', value: formatValue(product.value?.description?.dignities) },
  { label: 'Конструктив', value: formatValue(product.value?.description?.constructive) },
  { label: 'Avito 1', value: formatValue(product.value?.description?.avito1) },
  { label: 'Avito 2', value: formatValue(product.value?.description?.avito2) },
])

const attributes = computed(() => product.value?.attributes ?? [])
const attributeRows = computed(() =>
  attributes.value.map(item => ({
    id: item.id,
    name: item.name ?? item.attribute_id,
    value: item.value_number ?? item.value_string ?? EMPTY_TEXT,
  })),
)

const mediaItems = computed(() => product.value?.media ?? [])
const relations = computed(() => product.value?.relations ?? [])

const goBack = () => router.push({ path: '/products' })
</script>

<template>
  <div class="flex flex-column gap-4">
    <div class="flex flex-wrap align-items-start justify-between gap-3">
      <div class="flex flex-column gap-2">
        <div class="flex flex-wrap align-items-center gap-2">
          <h2 class="text-2xl font-semibold m-0">
            {{ product?.name ?? 'Товар' }}
          </h2>
          <Tag
            v-if="product?.is_visible"
            value="Видимый"
            severity="success"
          />
          <Tag
            v-if="product?.is_top"
            value="Топ"
            severity="info"
          />
          <Tag
            v-if="product?.is_new"
            value="Новый"
            severity="warning"
          />
        </div>
        <div class="text-sm text-muted">
          SKU: {{ product?.scu ?? EMPTY_TEXT }}
        </div>
      </div>
      <Button
        label="К товарам"
        icon="pi pi-arrow-left"
        outlined
        @click="goBack"
      />
    </div>

    <div
      v-if="isFetching"
      class="text-sm text-muted"
    >
      Загрузка...
    </div>

    <div
      v-if="error"
      class="p-3 border-round"
      style="background: #fee2e2; color: #b91c1c;"
    >
      Не удалось загрузить карточку товара.
    </div>

    <TabView v-model:activeIndex="activeTab">
      <TabPanel header="Основное">
        <div class="grid">
          <div class="col-12 md:col-7">
            <Card>
              <template #title>Основные данные</template>
              <template #content>
                <DataTable
                  :value="basicRows"
                  dataKey="label"
                  class="p-datatable-sm"
                >
                  <Column field="label" header="Поле" />
                  <Column field="value" header="Значение" />
                </DataTable>
              </template>
            </Card>
          </div>
          <div class="col-12 md:col-5">
            <Card>
              <template #title>Статусы</template>
              <template #content>
                <div class="flex flex-column gap-3">
                  <div class="flex align-items-center justify-content-between">
                    <span>Видимость</span>
                    <i
                      class="pi"
                      :class="product?.is_visible ? 'pi-eye' : 'pi-eye-slash'"
                      :style="{ color: product?.is_visible ? '#16a34a' : '#94a3b8' }"
                    />
                  </div>
                  <div class="flex align-items-center justify-content-between">
                    <span>Топ</span>
                    <i
                      class="pi"
                      :class="product?.is_top ? 'pi-star-fill' : 'pi-star'"
                      :style="{ color: product?.is_top ? '#2563eb' : '#94a3b8' }"
                    />
                  </div>
                  <div class="flex align-items-center justify-content-between">
                    <span>Новый</span>
                    <i
                      class="pi"
                      :class="product?.is_new ? 'pi-tag' : 'pi-tag'"
                      :style="{ color: product?.is_new ? '#0ea5e9' : '#94a3b8' }"
                    />
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>
      </TabPanel>

      <TabPanel header="Цены">
        <Card>
          <template #title>Цены</template>
          <template #content>
            <DataTable
              :value="priceRows"
              dataKey="label"
              class="p-datatable-sm"
            >
              <Column field="label" header="Поле" />
              <Column field="value" header="Значение" />
            </DataTable>
          </template>
        </Card>
      </TabPanel>

      <TabPanel header="Описание">
        <Card>
          <template #title>Описание</template>
          <template #content>
            <DataTable
              :value="descriptionRows"
              dataKey="label"
              class="p-datatable-sm"
            >
              <Column field="label" header="Поле" />
              <Column field="value" header="Значение" />
            </DataTable>
          </template>
        </Card>
      </TabPanel>

      <TabPanel header="Свойства">
        <Card>
          <template #title>Свойства</template>
          <template #content>
            <div v-if="attributeRows.length">
              <DataTable
                :value="attributeRows"
                dataKey="id"
                class="p-datatable-sm"
              >
                <Column field="name" header="Свойство" />
                <Column field="value" header="Значение" />
              </DataTable>
            </div>
            <div v-else class="text-sm text-muted">
              Нет свойств для этого товара.
            </div>
          </template>
        </Card>
      </TabPanel>

      <TabPanel header="Медиа">
        <div v-if="mediaItems.length" class="grid">
          <div
            v-for="item in mediaItems"
            :key="item.id"
            class="col-12 md:col-6 lg:col-4"
          >
            <Card>
              <template #title>
                <div class="flex align-items-center justify-content-between gap-2">
                  <span class="text-base">{{ item.type ?? 'media' }}</span>
                  <span
                    v-if="item.sort_order !== undefined"
                    class="text-xs text-muted"
                  >
                    #{{ item.sort_order }}
                  </span>
                </div>
              </template>
              <template #content>
                <img
                  v-if="item.type === 'image' && item.url"
                  :src="item.url"
                  class="w-full border-round"
                  style="max-height: 180px; object-fit: cover;"
                >
                <div v-else class="text-sm text-muted flex align-items-center gap-2">
                  <i class="pi pi-video" />
                  <span>{{ item.url ?? EMPTY_TEXT }}</span>
                </div>
              </template>
            </Card>
          </div>
        </div>
        <div v-else class="text-sm text-muted">
          Медиа пока не добавлены.
        </div>
      </TabPanel>

      <TabPanel header="Связи">
        <Card>
          <template #title>Связанные товары</template>
          <template #content>
            <div v-if="relations.length">
              <DataTable
                :value="relations"
                dataKey="id"
                class="p-datatable-sm"
              >
                <Column field="relation_type" header="Тип" />
                <Column header="Товар">
                  <template #body="{ data: row }">
                    {{ row.related_product?.name ?? EMPTY_TEXT }}
                  </template>
                </Column>
                <Column header="SCU">
                  <template #body="{ data: row }">
                    {{ row.related_product?.scu ?? EMPTY_TEXT }}
                  </template>
                </Column>
              </DataTable>
            </div>
            <div v-else class="text-sm text-muted">
              Нет связей.
            </div>
          </template>
        </Card>
      </TabPanel>
    </TabView>
  </div>
</template>
