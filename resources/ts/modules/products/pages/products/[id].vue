<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { productEndpoint } from '@/modules/products/api/products.api'
import type { Product } from '@/modules/products/types/products.types'
import {
  PRODUCT_BASIC_FIELDS,
  PRODUCT_DESCRIPTION_FIELDS,
  PRODUCT_DETAILS_CARDS,
  PRODUCT_DETAILS_EMPTY_TEXT,
  PRODUCT_DETAILS_FLAGS,
  PRODUCT_DETAILS_LABELS,
  PRODUCT_DETAILS_TABLE_HEADERS,
  PRODUCT_DETAILS_TABS,
  PRODUCT_PRICE_FIELDS,
  formatProductDate,
  formatProductPrice,
  formatProductValue,
} from '@/modules/products/config/productDetails.config'

const route = useRoute()
const router = useRouter()
const activeTab = ref(0)

const productId = computed(() => {
  const raw = (route.params as Record<string, string | string[] | undefined>).id
  if (Array.isArray(raw)) return raw[0] ?? ''
  return raw ?? ''
})

const endpoint = computed(() => productEndpoint(productId.value ?? '').value)
const { data, error, isFetching, execute: fetchProduct } = await useApi<{ data: Product }>(endpoint)

watch(productId, () => fetchProduct())

const product = computed(() => data.value?.data)

const formatValue = formatProductValue
const formatDate = formatProductDate
const formatPrice = formatProductPrice

const basicRows = computed(() => [
  { label: 'SCU', value: formatValue(product.value?.scu) },
  { label: PRODUCT_BASIC_FIELDS.kind, value: formatValue(product.value?.kind?.name) },
  { label: PRODUCT_BASIC_FIELDS.category, value: formatValue(product.value?.category?.name) },
  { label: PRODUCT_BASIC_FIELDS.subCategory, value: formatValue(product.value?.sub_category?.name) },
  { label: PRODUCT_BASIC_FIELDS.brand, value: formatValue(product.value?.brand?.name) },
  { label: PRODUCT_BASIC_FIELDS.productType, value: formatValue(product.value?.product_type_id) },
  { label: PRODUCT_BASIC_FIELDS.unit, value: formatValue(product.value?.unit_id) },
  { label: PRODUCT_BASIC_FIELDS.updatedAt, value: formatDate(product.value?.updated_at as string) },
])

const priceRows = computed(() => [
  { label: PRODUCT_PRICE_FIELDS.price, value: formatPrice(product.value?.price ?? null) },
  { label: PRODUCT_PRICE_FIELDS.priceSale, value: formatPrice(product.value?.price_sale ?? null) },
  { label: PRODUCT_PRICE_FIELDS.priceVendor, value: formatPrice(product.value?.price_vendor ?? null) },
  { label: PRODUCT_PRICE_FIELDS.priceVendorMin, value: formatPrice(product.value?.price_vendor_min ?? null) },
  { label: PRODUCT_PRICE_FIELDS.priceZakup, value: formatPrice(product.value?.price_zakup ?? null) },
  { label: PRODUCT_PRICE_FIELDS.priceDelivery, value: formatPrice(product.value?.price_delivery ?? null) },
  { label: PRODUCT_PRICE_FIELDS.montaj, value: formatPrice(product.value?.montaj ?? null) },
  { label: PRODUCT_PRICE_FIELDS.montajSebest, value: formatPrice(product.value?.montaj_sebest ?? null) },
])

const descriptionRows = computed(() => [
  { label: PRODUCT_DESCRIPTION_FIELDS.short, value: formatValue(product.value?.description?.description_short) },
  { label: PRODUCT_DESCRIPTION_FIELDS.long, value: formatValue(product.value?.description?.description_long) },
  { label: PRODUCT_DESCRIPTION_FIELDS.dignities, value: formatValue(product.value?.description?.dignities) },
  { label: PRODUCT_DESCRIPTION_FIELDS.constructive, value: formatValue(product.value?.description?.constructive) },
  { label: PRODUCT_DESCRIPTION_FIELDS.avito1, value: formatValue(product.value?.description?.avito1) },
  { label: PRODUCT_DESCRIPTION_FIELDS.avito2, value: formatValue(product.value?.description?.avito2) },
])

const attributes = computed(() => product.value?.attributes ?? [])
const attributeRows = computed(() =>
  attributes.value.map(item => ({
    id: item.id,
    name: item.name ?? item.attribute_id,
    value: item.value_number ?? item.value_string ?? PRODUCT_DETAILS_EMPTY_TEXT,
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
            {{ product?.name ?? PRODUCT_DETAILS_LABELS.titleFallback }}
          </h2>
          <Tag
            v-if="product?.is_visible"
            :value="PRODUCT_DETAILS_FLAGS.visible"
            severity="success"
          />
          <Tag
            v-if="product?.is_top"
            :value="PRODUCT_DETAILS_FLAGS.top"
            severity="info"
          />
          <Tag
            v-if="product?.is_new"
            :value="PRODUCT_DETAILS_FLAGS.new"
            severity="warning"
          />
        </div>
        <div class="text-sm text-muted">
          {{ PRODUCT_DETAILS_LABELS.skuLabel }}: {{ product?.scu ?? PRODUCT_DETAILS_EMPTY_TEXT }}
        </div>
      </div>
      <Button
        :label="PRODUCT_DETAILS_LABELS.backButton"
        icon="pi pi-arrow-left"
        outlined
        @click="goBack"
      />
    </div>

    <div
      v-if="isFetching"
      class="text-sm text-muted"
    >
      {{ PRODUCT_DETAILS_LABELS.loading }}
    </div>

    <div
      v-if="error"
      class="p-3 border-round"
      style="background: #fee2e2; color: #b91c1c;"
    >
      {{ PRODUCT_DETAILS_LABELS.error }}
    </div>

    <TabView v-model:activeIndex="activeTab">
      <TabPanel :header="PRODUCT_DETAILS_TABS.overview" :value="0">
        <div class="grid">
          <div class="col-12 md:col-7">
            <Card>
              <template #title>{{ PRODUCT_DETAILS_CARDS.basic }}</template>
              <template #content>
                <DataTable
                  :value="basicRows"
                  dataKey="label"
                  class="p-datatable-sm"
                >
                  <Column field="label" :header="PRODUCT_DETAILS_TABLE_HEADERS.field" />
                  <Column field="value" :header="PRODUCT_DETAILS_TABLE_HEADERS.value" />
                </DataTable>
              </template>
            </Card>
          </div>
          <div class="col-12 md:col-5">
            <Card>
              <template #title>{{ PRODUCT_DETAILS_CARDS.status }}</template>
              <template #content>
                <div class="flex flex-column gap-3">
                  <div class="flex align-items-center justify-content-between">
                    <span>{{ PRODUCT_DETAILS_FLAGS.visible }}</span>
                    <i
                      class="pi"
                      :class="product?.is_visible ? 'pi-eye' : 'pi-eye-slash'"
                      :style="{ color: product?.is_visible ? '#16a34a' : '#94a3b8' }"
                    />
                  </div>
                  <div class="flex align-items-center justify-content-between">
                    <span>{{ PRODUCT_DETAILS_FLAGS.top }}</span>
                    <i
                      class="pi"
                      :class="product?.is_top ? 'pi-star-fill' : 'pi-star'"
                      :style="{ color: product?.is_top ? '#2563eb' : '#94a3b8' }"
                    />
                  </div>
                  <div class="flex align-items-center justify-content-between">
                    <span>{{ PRODUCT_DETAILS_FLAGS.new }}</span>
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

      <TabPanel :header="PRODUCT_DETAILS_TABS.prices" :value="1">
        <Card>
          <template #title>{{ PRODUCT_DETAILS_CARDS.prices }}</template>
          <template #content>
            <DataTable
              :value="priceRows"
              dataKey="label"
              class="p-datatable-sm"
            >
              <Column field="label" :header="PRODUCT_DETAILS_TABLE_HEADERS.field" />
              <Column field="value" :header="PRODUCT_DETAILS_TABLE_HEADERS.value" />
            </DataTable>
          </template>
        </Card>
      </TabPanel>

      <TabPanel :header="PRODUCT_DETAILS_TABS.descriptions" :value="2">
        <Card>
          <template #title>{{ PRODUCT_DETAILS_CARDS.descriptions }}</template>
          <template #content>
            <DataTable
              :value="descriptionRows"
              dataKey="label"
              class="p-datatable-sm"
            >
              <Column field="label" :header="PRODUCT_DETAILS_TABLE_HEADERS.field" />
              <Column field="value" :header="PRODUCT_DETAILS_TABLE_HEADERS.value" />
            </DataTable>
          </template>
        </Card>
      </TabPanel>

      <TabPanel :header="PRODUCT_DETAILS_TABS.attributes" :value="3">
        <Card>
          <template #title>{{ PRODUCT_DETAILS_CARDS.attributes }}</template>
          <template #content>
            <div v-if="attributeRows.length">
              <DataTable
                :value="attributeRows"
                dataKey="id"
                class="p-datatable-sm"
              >
                <Column field="name" :header="PRODUCT_DETAILS_TABLE_HEADERS.attributeName" />
                <Column field="value" :header="PRODUCT_DETAILS_TABLE_HEADERS.attributeValue" />
              </DataTable>
            </div>
            <div v-else class="text-sm text-muted">
              {{ PRODUCT_DETAILS_LABELS.noAttributes }}
            </div>
          </template>
        </Card>
      </TabPanel>

      <TabPanel :header="PRODUCT_DETAILS_TABS.media" :value="4">
        <div v-if="mediaItems.length" class="grid">
          <div
            v-for="item in mediaItems"
            :key="item.id"
            class="col-12 md:col-6 lg:col-4"
          >
            <Card>
              <template #title>
                <div class="flex align-items-center justify-content-between gap-2">
                  <span class="text-base">{{ item.type ?? PRODUCT_DETAILS_LABELS.mediaTypeFallback }}</span>
                  <span
                    v-if="item.sort_order !== undefined"
                    class="text-xs text-muted"
                  >
                    {{ PRODUCT_DETAILS_LABELS.mediaSortPrefix }}{{ item.sort_order }}
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
                  <span>{{ item.url ?? PRODUCT_DETAILS_EMPTY_TEXT }}</span>
                </div>
              </template>
            </Card>
          </div>
        </div>
        <div v-else class="text-sm text-muted">
          {{ PRODUCT_DETAILS_LABELS.noMedia }}
        </div>
      </TabPanel>

      <TabPanel :header="PRODUCT_DETAILS_TABS.relations" :value="5">
        <Card>
          <template #title>{{ PRODUCT_DETAILS_CARDS.relations }}</template>
          <template #content>
            <div v-if="relations.length">
              <DataTable
                :value="relations"
                dataKey="id"
                class="p-datatable-sm"
              >
                <Column field="relation_type" :header="PRODUCT_DETAILS_TABLE_HEADERS.relationType" />
                <Column :header="PRODUCT_DETAILS_TABLE_HEADERS.relationProduct">
                  <template #body="{ data: row }">
                    {{ row.related_product?.name ?? PRODUCT_DETAILS_EMPTY_TEXT }}
                  </template>
                </Column>
                <Column :header="PRODUCT_DETAILS_TABLE_HEADERS.relationScu">
                  <template #body="{ data: row }">
                    {{ row.related_product?.scu ?? PRODUCT_DETAILS_EMPTY_TEXT }}
                  </template>
                </Column>
              </DataTable>
            </div>
            <div v-else class="text-sm text-muted">
              {{ PRODUCT_DETAILS_LABELS.noRelations }}
            </div>
          </template>
        </Card>
      </TabPanel>
    </TabView>
  </div>
</template>
