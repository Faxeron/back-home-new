<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Divider from 'primevue/divider'
import Tag from 'primevue/tag'
import AutoComplete from 'primevue/autocomplete'
import { $api } from '@/utils/api'
import type { Estimate, EstimateItem } from '@/types/estimates'
import type { Product } from '@/types/products'

const props = defineProps<{ estimateId?: number | null }>()

const router = useRouter()
const route = useRoute()

const estimateId = ref<number | null>(props.estimateId ?? null)
const estimate = ref<Estimate | null>(null)

type EstimateItemRow = EstimateItem & { groupLabel: string; typeLabel: string; groupOrder: number }

const items = ref<EstimateItemRow[]>([])
const loading = ref(false)
const saving = ref(false)
const errorMessage = ref('')

const form = reactive({
  client_name: '',
  client_phone: '',
  site_address: '',
})

const addSku = ref('')
const addQty = ref<number | null>(1)
const productSearch = ref<Product | string | null>(null)
const productSuggestions = ref<Product[]>([])
const productLoading = ref(false)
const selectedProduct = ref<Product | null>(null)
const hasTemplate = ref(false)
const templateCheckLoading = ref(false)

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

const decorateItems = (rows: EstimateItem[]): EstimateItemRow[] =>
  rows.map(item => ({
    ...item,
    groupLabel: item.group?.name ?? 'Без группы',
    typeLabel: item.product?.product_type_id
      ? PRODUCT_TYPE_LABELS[item.product.product_type_id] ?? `Тип ${item.product.product_type_id}`
      : '-',
    groupOrder: item.product?.product_type_id
      ? GROUP_ORDER[item.product.product_type_id] ?? 99
      : 99,
  }))

const itemsSorted = computed(() => {
  const list = items.value.slice()
  list.sort((a, b) => {
    if (a.groupOrder !== b.groupOrder) {
      return a.groupOrder - b.groupOrder
    }
    if (a.groupLabel !== b.groupLabel) {
      return a.groupLabel.localeCompare(b.groupLabel)
    }
    return (a.sort_order ?? 0) - (b.sort_order ?? 0)
  })
  return list
})

const groupTotals = computed(() => {
  const totals: Record<string, number> = {}
  for (const item of items.value) {
    const key = item.groupLabel
    totals[key] = (totals[key] ?? 0) + (item.total ?? 0)
  }
  return totals
})

const grandTotal = computed(() =>
  items.value.reduce((sum, item) => sum + (item.total ?? 0), 0),
)

const formatCurrency = (value?: number | null) =>
  typeof value === 'number'
    ? value.toLocaleString('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 })
    : '-'

const buildPublicLink = (path?: string | null) => {
  if (!path) return null
  if (path.startsWith('http://') || path.startsWith('https://')) return path
  if (typeof window === 'undefined') return path
  const normalized = path.startsWith('/') ? path : `/${path}`
  return `${window.location.origin}${normalized}`
}

const clientLink = computed(() => buildPublicLink(estimate.value?.link ?? null))
const montajLink = computed(() => buildPublicLink(estimate.value?.link_montaj ?? null))

const loadEstimate = async () => {
  if (!estimateId.value) return
  loading.value = true
  errorMessage.value = ''
  try {
    const response = await $api(`/estimates/${estimateId.value}`)
    const data = response?.data as Estimate | undefined
    if (!data) return
    estimate.value = data
    form.client_name = data.client_name ?? ''
    form.client_phone = data.client_phone ?? ''
    form.site_address = data.site_address ?? ''
    items.value = decorateItems(data.items ?? [])
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось загрузить смету.'
  } finally {
    loading.value = false
  }
}

const ensureEstimate = async (isDraft = true) => {
  if (estimateId.value) return estimateId.value
  if (!isDraft && !form.client_name.trim()) {
    errorMessage.value = 'Имя клиента обязательно.'
    return null
  }

  saving.value = true
  errorMessage.value = ''
  try {
    const response = await $api('/estimates', {
      method: 'POST',
      body: {
        client_name: isDraft ? (form.client_name.trim() || null) : form.client_name,
        client_phone: form.client_phone || null,
        site_address: form.site_address || null,
        draft: isDraft,
      },
    })
    const data = response?.data as Estimate | undefined
    if (data) {
      estimate.value = data
      estimateId.value = data.id
      await router.replace({ path: `/estimates/${data.id}/edit` })
      return data.id
    }
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось создать смету.'
  } finally {
    saving.value = false
  }

  return null
}

const saveEstimate = async () => {
  if (!form.client_name.trim()) {
    errorMessage.value = 'Имя клиента обязательно.'
    return
  }
  if (!estimateId.value) {
    await ensureEstimate(false)
    return
  }

  saving.value = true
  errorMessage.value = ''
  try {
    const response = await $api(`/estimates/${estimateId.value}`, {
      method: 'PATCH',
      body: {
        client_name: form.client_name,
        client_phone: form.client_phone || null,
        site_address: form.site_address || null,
        draft: false,
      },
    })
    const data = response?.data as Estimate | undefined
    if (data) {
      estimate.value = data
    }
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось сохранить смету.'
  } finally {
    saving.value = false
  }
}

const refreshItems = async () => {
  if (!estimateId.value) return
  await loadEstimate()
}

const fetchProductSuggestions = async (query: string) => {
  const search = query.trim()
  if (!search) {
    productSuggestions.value = []
    return
  }

  productLoading.value = true
  try {
    const response = await $api('/products', {
      query: {
        q: search,
        per_page: 10,
      },
    })
    productSuggestions.value = response?.data ?? []
  } catch (error) {
    productSuggestions.value = []
  } finally {
    productLoading.value = false
  }
}

const handleProductSearch = async (event: { query: string }) => {
  await fetchProductSuggestions(event.query)
}

const checkTemplateForSku = async (sku?: string | null) => {
  const normalized = sku?.trim() ?? ''
  if (!normalized) {
    hasTemplate.value = false
    return
  }

  templateCheckLoading.value = true
  try {
    const response = await $api('/estimate-templates/septiks', {
      query: {
        sku: normalized,
        per_page: 1,
      },
    })
    const list = response?.data ?? []
    hasTemplate.value = Array.isArray(list) && list.length > 0
  } catch (error) {
    hasTemplate.value = false
  } finally {
    templateCheckLoading.value = false
  }
}

const handleProductSelect = async (event: { value: Product }) => {
  selectedProduct.value = event.value
  addSku.value = event.value?.scu ?? ''
  await checkTemplateForSku(event.value?.scu)
}

const clearSelectedProduct = () => {
  selectedProduct.value = null
  addSku.value = ''
  hasTemplate.value = false
}

watch(productSearch, value => {
  if (!value || typeof value === 'string') {
    clearSelectedProduct()
  }
})

const addItemBySku = async () => {
  const id = await ensureEstimate(true)
  if (!id) return
  if (!addSku.value.trim()) return

  saving.value = true
  errorMessage.value = ''
  try {
    await $api(`/estimates/${id}/items`, {
      method: 'POST',
      body: {
        scu: addSku.value.trim(),
        qty: addQty.value ?? 1,
      },
    })
    addSku.value = ''
    addQty.value = 1
    productSearch.value = null
    clearSelectedProduct()
    await refreshItems()
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось добавить позицию.'
  } finally {
    saving.value = false
  }
}

const addItemWithTemplate = async () => {
  const id = await ensureEstimate(true)
  if (!id) return
  if (!addSku.value.trim()) return

  saving.value = true
  errorMessage.value = ''
  try {
    await $api(`/estimates/${id}/items`, {
      method: 'POST',
      body: {
        scu: addSku.value.trim(),
        qty: addQty.value ?? 1,
      },
    })

    const response = await $api(`/estimates/${id}/apply-template`, {
      method: 'POST',
      body: {
        root_scu: addSku.value.trim(),
        root_qty: addQty.value ?? 1,
      },
    })
    const data = response?.data as EstimateItem[] | undefined
    if (data) {
      items.value = decorateItems(data)
    } else {
      await refreshItems()
    }
    productSearch.value = null
    addQty.value = 1
    clearSelectedProduct()
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось применить шаблон.'
  } finally {
    saving.value = false
  }
}

const updateItemQty = async (item: EstimateItemRow) => {
  if (!estimateId.value) return
  saving.value = true
  errorMessage.value = ''
  try {
    await $api(`/estimates/${estimateId.value}/items/${item.id}`, {
      method: 'PATCH',
      body: {
        qty: item.qty,
      },
    })
    await refreshItems()
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось обновить количество.'
  } finally {
    saving.value = false
  }
}

const updateItemPrice = async (item: EstimateItemRow) => {
  if (!estimateId.value) return
  saving.value = true
  errorMessage.value = ''
  try {
    await $api(`/estimates/${estimateId.value}/items/${item.id}`, {
      method: 'PATCH',
      body: {
        price: item.price,
      },
    })
    await refreshItems()
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось обновить цену.'
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  if (estimateId.value || route.params.id) {
    estimateId.value = estimateId.value ?? Number(route.params.id)
    await loadEstimate()
  }
})
</script>

<template>
  <div class="flex flex-column gap-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div class="flex flex-column gap-2">
        <h2 class="text-xl font-semibold">
          {{ estimateId ? `Смета #${estimateId}` : 'Создание сметы' }}
        </h2>
        <div v-if="clientLink || montajLink" class="estimate-links">
          <div v-if="clientLink" class="estimate-link-card">
            <div class="estimate-link-label">Клиент</div>
            <a
              :href="clientLink"
              class="estimate-link-action"
              target="_blank"
              rel="noopener noreferrer"
            >
              <i class="pi pi-external-link" aria-hidden="true" />
              Открыть смету
            </a>
            <div class="estimate-link-url">{{ clientLink }}</div>
          </div>
          <div v-if="montajLink" class="estimate-link-card">
            <div class="estimate-link-label">Монтажник</div>
            <a
              :href="montajLink"
              class="estimate-link-action"
              target="_blank"
              rel="noopener noreferrer"
            >
              <i class="pi pi-external-link" aria-hidden="true" />
              Открыть смету
            </a>
            <div class="estimate-link-url">{{ montajLink }}</div>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Button
          label="Сохранить"
          icon="pi pi-save"
          :loading="saving"
          @click="saveEstimate"
        />
      </div>
    </div>

    <Card>
      <template #title>Клиент</template>
      <template #content>
        <div class="grid">
          <div class="col-12 md:col-4">
            <label class="text-sm text-muted">Имя *</label>
            <InputText v-model="form.client_name" class="w-full" placeholder="Имя клиента" />
          </div>
          <div class="col-12 md:col-4">
            <label class="text-sm text-muted">Телефон</label>
            <InputText v-model="form.client_phone" class="w-full" placeholder="+7..." />
          </div>
          <div class="col-12 md:col-4">
            <label class="text-sm text-muted">Адрес участка</label>
            <InputText v-model="form.site_address" class="w-full" placeholder="Адрес" />
          </div>
        </div>
        <div v-if="estimate?.counterparty" class="mt-4">
          <div class="mb-2 text-sm text-muted">Клиент (снимок из справочника)</div>
          <div class="grid">
            <div class="col-12 md:col-3">
              <label class="text-sm text-muted">ID контрагента</label>
              <InputText :model-value="String(estimate.counterparty?.id ?? '')" class="w-full" disabled />
            </div>
            <div class="col-12 md:col-3">
              <label class="text-sm text-muted">Тип</label>
              <InputText :model-value="estimate.counterparty?.type ?? ''" class="w-full" disabled />
            </div>
            <div class="col-12 md:col-3">
              <label class="text-sm text-muted">Имя</label>
              <InputText :model-value="estimate.counterparty?.name ?? ''" class="w-full" disabled />
            </div>
            <div class="col-12 md:col-3">
              <label class="text-sm text-muted">Телефон</label>
              <InputText :model-value="estimate.counterparty?.phone ?? ''" class="w-full" disabled />
            </div>
          </div>
        </div>
      </template>
    </Card>

    <div class="grid">
      <div class="col-12">
        <Card>
          <template #title>Добавить позицию</template>
          <template #content>
            <div class="flex flex-column gap-3">
              <div class="grid">
                <div class="col-12 md:col-7">
                  <label class="text-sm text-muted">Товар</label>
                  <AutoComplete
                    v-model="productSearch"
                    :suggestions="productSuggestions"
                    optionLabel="name"
                    :loading="productLoading"
                    forceSelection
                    class="w-full"
                    placeholder="Начните вводить название"
                    @complete="handleProductSearch"
                    @item-select="handleProductSelect"
                  />
                </div>
                <div class="col-12 md:col-5">
                  <label class="text-sm text-muted">Кол-во</label>
                  <InputNumber v-model="addQty" class="w-full" :min="0" />
                </div>
              </div>
              <div class="flex flex-wrap items-center gap-2">
                <Button
                  label="Добавить"
                  icon="pi pi-plus"
                  :loading="saving"
                  :disabled="!addSku.trim()"
                  @click="addItemBySku"
                />
                <Button
                  v-if="hasTemplate"
                  label="Добавить по шаблону"
                  icon="pi pi-bolt"
                  severity="secondary"
                  :loading="saving || templateCheckLoading"
                  :disabled="!addSku.trim()"
                  @click="addItemWithTemplate"
                />
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>

    <Card>
      <template #title>Позиции сметы</template>
      <template #content>
        <div v-if="errorMessage" class="mb-3 text-sm" style="color: #dc2626">
          {{ errorMessage }}
        </div>
        <DataTable
          :value="itemsSorted"
          dataKey="id"
          :loading="loading"
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
          <template #groupfooter="{ data }">
            <td colspan="8" class="estimate-group-summary">
              <div class="flex w-full justify-end text-sm font-medium">
                Итог: {{ formatCurrency(groupTotals[data.groupLabel]) }}
              </div>
            </td>
          </template>

          <Column field="product.scu" header="SKU" style="inline-size: 12ch;">
            <template #body="{ data }">
              {{ data.product?.scu ?? '—' }}
            </template>
          </Column>
          <Column field="product.name" header="Название">
            <template #body="{ data }">
              {{ data.product?.name ?? '—' }}
            </template>
          </Column>
          <Column field="typeLabel" header="Тип" style="inline-size: 12ch;">
            <template #body="{ data }">
              {{ data.typeLabel }}
            </template>
          </Column>
          <Column field="product.unit" header="Ед." style="inline-size: 8ch;">
            <template #body="{ data }">
              {{ data.product?.unit?.name ?? '—' }}
            </template>
          </Column>
          <Column field="qty" header="Кол-во" style="inline-size: 10ch;">
            <template #body="{ data }">
              <InputNumber
                v-model="data.qty"
                class="w-full"
                :min="0"
                :step="1"
                showButtons
                buttonLayout="horizontal"
                incrementButtonIcon="pi pi-plus"
                decrementButtonIcon="pi pi-minus"
                @blur="updateItemQty(data)"
              />
            </template>
          </Column>
          <Column field="price" header="Цена" style="inline-size: 12ch;">
            <template #body="{ data }">
              <InputNumber
                v-model="data.price"
                class="w-full"
                :min="0"
                @blur="updateItemPrice(data)"
              />
            </template>
          </Column>
          <Column field="total" header="Сумма" style="inline-size: 14ch;">
            <template #body="{ data }">
              {{ formatCurrency(data.total) }}
            </template>
          </Column>
          <Column field="groupLabel" header="Группа" style="inline-size: 16ch;">
            <template #body="{ data }">
              {{ data.groupLabel }}
            </template>
          </Column>
          <template #empty>
            <div class="text-center py-6 text-muted">Добавьте позиции в смету.</div>
          </template>
        </DataTable>
        <Divider />
        <div class="flex justify-end">
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

.estimate-links {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 4px;
}

.estimate-link-card {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 10px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #f8fafc;
  min-width: 260px;
}

.estimate-link-label {
  font-size: 12px;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.estimate-link-action {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #0f766e;
  font-weight: 600;
  text-decoration: none;
}

.estimate-link-action:hover {
  text-decoration: underline;
}

.estimate-link-url {
  font-size: 12px;
  color: #475569;
  word-break: break-all;
}
</style>
