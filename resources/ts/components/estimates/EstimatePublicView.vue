<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Divider from 'primevue/divider'
import Tag from 'primevue/tag'
import ProgressSpinner from 'primevue/progressspinner'
import { $api } from '@/utils/api'

const props = withDefaults(defineProps<{
  randomId: string
  hidePrices?: boolean
}>(), {
  hidePrices: false,
})

const route = useRoute()
const hidePrices = computed(() => props.hidePrices || route.path.includes('/montaj'))

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

const PRODUCT_TYPE_LABELS: Record<number, string> = {
  1: 'Материал',
  2: 'Товар',
  3: 'Работа',
  4: 'Услуга',
  5: 'Транспорт',
  6: 'Субподряд',
}

const GROUP_ORDER: Record<number, number> = {
  2: 1, // Товар
  1: 2, // Материал
  3: 3, // Работа
  5: 4, // Транспорт
  4: 5, // Услуга
  6: 6, // Субподряд
}

const decorateItems = (rows: PublicEstimateItem[]): PublicEstimateItemRow[] =>
  rows.map(item => {
    const typeLabel = item.product?.product_type_id
      ? PRODUCT_TYPE_LABELS[item.product.product_type_id] ?? `Тип ${item.product.product_type_id}`
      : '-'

    return {
      ...item,
      typeLabel,
      groupLabel: item.group?.name ?? typeLabel ?? 'Без группы',
      groupOrder: item.product?.product_type_id
        ? GROUP_ORDER[item.product.product_type_id] ?? 99
        : 99,
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

const formatCurrency = (value?: number | null) =>
  typeof value === 'number'
    ? value.toLocaleString('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 })
    : '-'

const formatDate = (value?: string | null) =>
  value
    ? new Date(value).toLocaleDateString('ru-RU')
    : '-'

const summaryColspan = computed(() => (hidePrices.value ? 5 : 7))

const titleLabel = computed(() => (hidePrices.value ? 'Смета для монтажника' : 'Смета для клиента'))

const loadEstimate = async () => {
  if (!props.randomId) return
  loading.value = true
  errorMessage.value = ''
  try {
    const response = await $api(hidePrices.value ? `/estimate/${props.randomId}/montaj` : `/estimate/${props.randomId}`)
    const data = response?.data as PublicEstimate | undefined
    if (data) {
      estimate.value = data
      items.value = decorateItems(data.items ?? [])
    }
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Смета не найдена.'
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
              {{ estimate ? `Смета #${estimate.id}` : 'Смета' }}
            </h2>
            <div class="text-sm text-muted">
              Дата: {{ formatDate(estimate?.created_at) }}
            </div>
          </div>
          <Tag
            v-if="hidePrices"
            value="Без цен"
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
            <div class="text-sm text-muted">Клиент</div>
            <div class="text-base font-medium">{{ estimate?.client_name ?? '—' }}</div>
          </div>
          <div class="col-12 md:col-4">
            <div class="text-sm text-muted">Телефон</div>
            <div class="text-base font-medium">{{ estimate?.client_phone ?? '—' }}</div>
          </div>
          <div class="col-12 md:col-4">
            <div class="text-sm text-muted">Адрес участка</div>
            <div class="text-base font-medium">{{ estimate?.site_address ?? '—' }}</div>
          </div>
        </div>
      </template>
    </Card>

    <Card v-if="!loading && !errorMessage">
      <template #title>Позиции сметы</template>
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
                Итог: {{ formatCurrency(groupTotals[data.groupLabel]) }}
              </div>
            </td>
          </template>

          <Column field="product.scu" header="SKU" style="inline-size: 12ch;">
            <template #body="{ data }">
              {{ data.product?.scu ?? '-' }}
            </template>
          </Column>
          <Column field="product.name" header="Название">
            <template #body="{ data }">
              {{ data.product?.name ?? '-' }}
            </template>
          </Column>
          <Column field="typeLabel" header="Тип" style="inline-size: 12ch;">
            <template #body="{ data }">
              {{ data.typeLabel }}
            </template>
          </Column>
          <Column field="product.unit" header="Ед." style="inline-size: 8ch;">
            <template #body="{ data }">
              {{ data.product?.unit?.name ?? '-' }}
            </template>
          </Column>
          <Column field="qty" header="Кол-во" style="inline-size: 10ch;">
            <template #body="{ data }">
              {{ data.qty }}
            </template>
          </Column>
          <Column
            v-if="!hidePrices"
            field="price"
            header="Цена"
            style="inline-size: 12ch;"
          >
            <template #body="{ data }">
              {{ formatCurrency(data.price) }}
            </template>
          </Column>
          <Column
            v-if="!hidePrices"
            field="total"
            header="Сумма"
            style="inline-size: 14ch;"
          >
            <template #body="{ data }">
              {{ formatCurrency(data.total) }}
            </template>
          </Column>

          <template #empty>
            <div class="text-center py-6 text-muted">Позиции не найдены.</div>
          </template>
        </DataTable>
        <Divider v-if="!hidePrices" />
        <div v-if="!hidePrices" class="flex justify-end">
          <span class="text-lg font-semibold">Итого по смете: {{ formatCurrency(grandTotal) }}</span>
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
