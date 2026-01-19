<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Divider from 'primevue/divider'
import Tag from 'primevue/tag'
import ProgressSpinner from 'primevue/progressspinner'
import { $api } from '@/utils/api'
import {
  DEFAULT_GROUP_LABEL,
  formatCurrency,
  getGroupOrder,
  getProductTypeLabel,
} from '@/modules/estimates/config/estimateTable.config'
import {
  PUBLIC_ESTIMATE_EMPTY_TEXT,
  PUBLIC_ESTIMATE_LABELS,
  formatPublicDate,
} from '@/modules/estimates/config/estimatePublic.config'
import {
  ESTIMATE_ITEM_HEADERS,
  ESTIMATE_ITEM_LABELS,
} from '@/modules/estimates/config/estimateItemsTable.config'

const props = withDefaults(defineProps<{
  randomId: string
  hidePrices?: boolean
}>(), {
  hidePrices: false,
})

const MONTAG_SUFFIX = 'mnt'
const hasMontajSuffix = computed(() => props.randomId.toLowerCase().endsWith(MONTAG_SUFFIX))
const baseRandomId = computed(() =>
  hasMontajSuffix.value
    ? props.randomId.slice(0, -MONTAG_SUFFIX.length)
    : props.randomId,
)
const hidePrices = computed(() => props.hidePrices || hasMontajSuffix.value)

interface PublicEstimateItem {
  id: number
  estimate_id?: number
  product_id?: number
  qty: number
  price?: number
  total?: number
  group?: {
    id?: number | null
    name?: string | null
  } | null
  product?: {
    id?: number | null
    name?: string | null
    scu?: string | null
    product_type_id?: number | null
    unit?: {
      id?: number | null
      code?: string | null
      name?: string | null
    } | null
  } | null
  sort_order?: number | null
}

interface PublicEstimate {
  id: number
  client_name?: string | null
  client_phone?: string | null
  site_address?: string | null
  created_at?: string | null
  updated_at?: string | null
  items_count?: number
  total_sum?: number
  items?: PublicEstimateItem[]
}

type PublicEstimateItemRow = PublicEstimateItem & { groupLabel: string; typeLabel: string; groupOrder: number }

const estimate = ref<PublicEstimate | null>(null)
const items = ref<PublicEstimateItemRow[]>([])
const loading = ref(false)
const errorMessage = ref('')

const decorateItems = (rows: PublicEstimateItem[]): PublicEstimateItemRow[] =>
  rows.map(item => {
    const typeLabel = getProductTypeLabel(item.product?.product_type_id)
    const groupLabel = item.group?.name ?? (typeLabel !== '-' ? typeLabel : DEFAULT_GROUP_LABEL)

    return {
      ...item,
      typeLabel,
      groupLabel,
      groupOrder: getGroupOrder(item.product?.product_type_id),
    }
  })

const itemsSorted = computed(() => {
  const list = items.value.slice()
  list.sort((a, b) => {
    if (a.groupOrder !== b.groupOrder) return a.groupOrder - b.groupOrder
    if (a.groupLabel !== b.groupLabel) return a.groupLabel.localeCompare(b.groupLabel)
    return (a.sort_order ?? 0) - (b.sort_order ?? 0)
  })
  return list
})

const groupTotals = computed(() => {
  const totals: Record<string, number> = {}
  if (hidePrices.value) return totals
  for (const item of items.value) {
    const key = item.groupLabel
    totals[key] = (totals[key] ?? 0) + (item.total ?? 0)
  }
  return totals
})

const grandTotal = computed(() =>
  hidePrices.value
    ? 0
    : items.value.reduce((sum, item) => sum + (item.total ?? 0), 0),
)

const summaryColspan = computed(() => (hidePrices.value ? 5 : 7))

const titleLabel = computed(() => (
  hidePrices.value ? PUBLIC_ESTIMATE_LABELS.titleMontaj : PUBLIC_ESTIMATE_LABELS.titleClient
))

const loadEstimate = async () => {
  if (!props.randomId) return
  loading.value = true
  errorMessage.value = ''
  try {
    const montajEndpoint = `/estimate/${baseRandomId.value}${MONTAG_SUFFIX}`
    const response = await $api(hidePrices.value ? montajEndpoint : `/estimate/${baseRandomId.value}`)
    const data = response?.data as PublicEstimate | undefined
    if (data) {
      estimate.value = data
      items.value = decorateItems(data.items ?? [])
    }
  } catch (error: any) {
    const message = error?.response?.data?.message ?? error?.data?.message
    errorMessage.value = message || PUBLIC_ESTIMATE_LABELS.notFound
    estimate.value = null
    items.value = []
  } finally {
    loading.value = false
  }
}

watch(() => props.randomId, () => {
  loadEstimate()
}, { immediate: true })
</script>

<template>
  <div class="public-estimate">
    <Card class="mb-4">
      <template #title>
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <div class="text-sm text-muted mb-1">{{ titleLabel }}</div>
            <h2 class="text-xl font-semibold">
              {{ estimate ? `Смета #${estimate.id}` : PUBLIC_ESTIMATE_LABELS.fallbackTitle }}
            </h2>
            <div class="text-sm text-muted">
              {{ PUBLIC_ESTIMATE_LABELS.date }}: {{ formatPublicDate(estimate?.created_at) }}
            </div>
          </div>
          <Tag
            v-if="hidePrices"
            :value="PUBLIC_ESTIMATE_LABELS.noPrices"
            severity="info"
          />
        </div>
      </template>
      <template #content>
        <div v-if="loading" class="flex justify-center py-5">
          <ProgressSpinner style="width: 40px; height: 40px" strokeWidth="3" />
        </div>
        <div v-else-if="errorMessage" class="text-sm text-red-600">
          {{ errorMessage }}
        </div>
        <div v-else class="grid">
          <div class="col-12 md:col-4">
            <div class="text-sm text-muted">{{ PUBLIC_ESTIMATE_LABELS.client }}</div>
            <div class="text-base font-medium">{{ estimate?.client_name ?? PUBLIC_ESTIMATE_EMPTY_TEXT }}</div>
          </div>
          <div class="col-12 md:col-4">
            <div class="text-sm text-muted">{{ PUBLIC_ESTIMATE_LABELS.phone }}</div>
            <div class="text-base font-medium">{{ estimate?.client_phone ?? PUBLIC_ESTIMATE_EMPTY_TEXT }}</div>
          </div>
          <div class="col-12 md:col-4">
            <div class="text-sm text-muted">{{ PUBLIC_ESTIMATE_LABELS.siteAddress }}</div>
            <div class="text-base font-medium">{{ estimate?.site_address ?? PUBLIC_ESTIMATE_EMPTY_TEXT }}</div>
          </div>
        </div>
      </template>
    </Card>

    <Card v-if="!loading && !errorMessage">
      <template #title>{{ ESTIMATE_ITEM_LABELS.title }}</template>
      <template #content>
        <DataTable
          :value="itemsSorted"
          dataKey="id"
          rowGroupMode="subheader"
          groupRowsBy="groupLabel"
          class="p-datatable-sm"
          stripedRows
        >
          <template #groupheader="{ data }">
            <div class="flex items-center gap-3 py-1">
              <span class="font-semibold">{{ data.groupLabel }}</span>
            </div>
          </template>
          <template v-if="!hidePrices" #groupfooter="{ data }">
            <td :colspan="summaryColspan" class="estimate-group-summary">
              <div class="flex w-full justify-end text-sm font-medium">
                {{ ESTIMATE_ITEM_LABELS.groupTotal }}: {{ formatCurrency(groupTotals[data.groupLabel]) }}
              </div>
            </td>
          </template>

          <Column field="product.scu" :header="ESTIMATE_ITEM_HEADERS.sku" style="inline-size: 12ch;">
            <template #body="{ data }">
              {{ data.product?.scu ?? PUBLIC_ESTIMATE_EMPTY_TEXT }}
            </template>
          </Column>
          <Column field="product.name" :header="ESTIMATE_ITEM_HEADERS.name">
            <template #body="{ data }">
              {{ data.product?.name ?? PUBLIC_ESTIMATE_EMPTY_TEXT }}
            </template>
          </Column>
          <Column field="typeLabel" :header="ESTIMATE_ITEM_HEADERS.type" style="inline-size: 12ch;">
            <template #body="{ data }">
              {{ data.typeLabel }}
            </template>
          </Column>
          <Column field="product.unit" :header="ESTIMATE_ITEM_HEADERS.unit" style="inline-size: 8ch;">
            <template #body="{ data }">
              {{ data.product?.unit?.name ?? PUBLIC_ESTIMATE_EMPTY_TEXT }}
            </template>
          </Column>
          <Column field="qty" :header="ESTIMATE_ITEM_HEADERS.qty" style="inline-size: 10ch;">
            <template #body="{ data }">
              {{ data.qty }}
            </template>
          </Column>
          <Column
            v-if="!hidePrices"
            field="price"
            :header="ESTIMATE_ITEM_HEADERS.price"
            style="inline-size: 12ch;"
          >
            <template #body="{ data }">
              {{ formatCurrency(data.price) }}
            </template>
          </Column>
          <Column
            v-if="!hidePrices"
            field="total"
            :header="ESTIMATE_ITEM_HEADERS.total"
            style="inline-size: 14ch;"
          >
            <template #body="{ data }">
              {{ formatCurrency(data.total) }}
            </template>
          </Column>

          <template #empty>
            <div class="text-center py-6 text-muted">{{ PUBLIC_ESTIMATE_LABELS.empty }}</div>
          </template>
        </DataTable>
        <Divider v-if="!hidePrices" />
        <div v-if="!hidePrices" class="flex justify-end">
          <span class="text-lg font-semibold">
            {{ ESTIMATE_ITEM_LABELS.total }}: {{ formatCurrency(grandTotal) }}
          </span>
        </div>
      </template>
    </Card>
  </div>
</template>

<style scoped>
.estimate-group-summary {
  background: #e8f4ff;
  display: flex;
  justify-content: flex-end;
  padding-right: 12px;
}
</style>
