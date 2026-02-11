<script setup lang="ts">
import CashboxCell from '@/components/cashboxes/CashboxCell.vue'
import { defaultTransactionFilters } from '@/modules/finance/composables/useTransactionFilters'
import { TRANSACTION_BOOLEAN_OPTIONS, TRANSACTION_COLUMNS } from '@/modules/finance/config/transactionsTable.config'
import { useDictionariesStore } from '@/stores/dictionaries'
import type { Transaction } from '@/types/finance'
import { $api } from '@/utils/api'
import { formatDateShort, formatSum } from '@/utils/formatters/finance'
import Button from 'primevue/button'
import Calendar from 'primevue/calendar'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Popover from 'primevue/popover'
import Select from 'primevue/select'
import { computed, reactive, ref, watch } from 'vue'

type TransactionRow = Transaction & {
  contract_or_counterparty?: string
  related?: string
}

const props = defineProps<{
  rows: Transaction[]
  loading: boolean
  totalRecords: number
  scrollHeight: string
  virtualScrollerOptions: Record<string, any>
  filters: any
  canDelete?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:filters', value: any): void
  (e: 'sort', event: any): void
  (e: 'reset-filters'): void
  (e: 'delete', row: Transaction): void
  (e: 'reload'): void
}>()

// Реактивный объект с гарантированной полной структурой — БАЗА ИСТИНЫ
const filtersModel = reactive<ReturnType<typeof defaultTransactionFilters>>(defaultTransactionFilters())

// Полностью переписываем объект при изменении props.filters (безопасное копирование)
const syncFiltersFromProps = (value: any) => {
  const incoming = (value ?? {}) as Record<string, any>
  const defaults = defaultTransactionFilters() as Record<string, any>

  const mergeFilterMeta = (base: any, next: any) => {
    if (!next || typeof next !== 'object') return { ...base }

    const merged: any = { ...base, ...next }

    // Merge nested "value" object for range filters (date/sum).
    const baseValue = base?.value
    const nextValue = next?.value
    if (nextValue === undefined) {
      merged.value = baseValue
    } else if (
      baseValue &&
      typeof baseValue === 'object' &&
      !Array.isArray(baseValue) &&
      nextValue &&
      typeof nextValue === 'object' &&
      !Array.isArray(nextValue)
    ) {
      merged.value = { ...baseValue, ...nextValue }
    }

    if (next?.matchMode === undefined) merged.matchMode = base?.matchMode

    return merged
  }

  const allowedKeys = Object.keys(defaults)

  // Drop any unknown keys to avoid PrimeVue DataTable crashes (e.g. undefined filter meta).
  for (const key of Object.keys(filtersModel as any)) {
    if (!allowedKeys.includes(key)) delete (filtersModel as any)[key]
  }

  for (const key of allowedKeys) {
    ;(filtersModel as any)[key] = mergeFilterMeta(defaults[key], incoming[key])
  }
}

watch(
  () => props.filters,
  value => syncFiltersFromProps(value),
  { deep: true, immediate: true },
)

// Отправляем обновления родителю с задержкой, чтобы избежать race conditions
let updateTimeout: any = null
const notifyParent = () => {
  clearTimeout(updateTimeout)
  updateTimeout = setTimeout(() => {
    // Глубокое копирование для изоляции от реактивности
    const snapshot = JSON.parse(JSON.stringify(filtersModel))
    emit('update:filters', snapshot)
  }, 0)
}

const totalLabel = computed(() => Number(props.totalRecords ?? 0).toLocaleString('ru-RU'))

const dictionaries = useDictionariesStore()

const cashboxInlineSize = computed(() => {
  const names = dictionaries.cashBoxes.map(item => item.name ?? '')
  const maxLength = names.reduce((max, name) => Math.max(max, name.length), 0)
  const ch = Math.max(maxLength, 6)
  return `calc(${ch}ch + 56px)`
})

const paidRangePanel = ref<any>(null)
const completedRangePanel = ref<any>(null)
const paymentMethodPanel = ref<any>(null)
const sumRangePanel = ref<any>(null)

const rows = computed<TransactionRow[]>(() =>
  (props.rows || []).map(row => ({
    ...row,
    contract_or_counterparty: [row.contract_id, row.counterparty?.name].filter(Boolean).join(' '),
    related: row.related_id != null ? String(row.related_id) : '',
  })),
)

const togglePanel = (panel: { toggle: (event: Event) => void } | null, event: Event) => {
  panel?.toggle(event)
}

const rowBooleanOptions = [
  { label: 'Да', value: true },
  { label: 'Нет', value: false },
]

const savingFields = reactive<Record<string, boolean>>({})

const fieldKey = (id: number, field: string) => `${id}:${field}`
const isSavingField = (id: number, field: string) => savingFields[fieldKey(id, field)] === true
const setSavingField = (id: number, field: string, value: boolean) => {
  savingFields[fieldKey(id, field)] = value
}

const toDateTimeInput = (value?: string | null) => {
  if (!value) return ''
  const source = String(value).trim()
  if (!source) return ''
  const normalized = source.replace(' ', 'T')
  return normalized.length >= 16 ? normalized.slice(0, 16) : normalized
}

const toApiDateTime = (value: string) => {
  const normalized = String(value ?? '').trim()
  if (!normalized) return null
  const withSpace = normalized.replace('T', ' ')
  return withSpace.length === 16 ? `${withSpace}:00` : withSpace
}

const updateTransaction = async (id: number, payload: Record<string, unknown>, field: string) => {
  setSavingField(id, field, true)
  try {
    await $api(`finance/transactions/${id}`, {
      method: 'PUT',
      body: payload,
    })
    emit('reload')
  } catch (error) {
    console.error('Failed to update transaction row', error)
  } finally {
    setSavingField(id, field, false)
  }
}

const updateBooleanField = async (
  row: TransactionRow,
  field: 'is_paid' | 'is_completed',
  value: boolean | null,
) => {
  if (value === null || value === undefined) return
  const current = field === 'is_paid' ? row.is_paid : row.is_completed
  if (Boolean(current) === Boolean(value)) return
  await updateTransaction(row.id, { [field]: Boolean(value) }, field)
}

const updateTransactionTimestamp = async (
  row: TransactionRow,
  field: 'created_at' | 'updated_at',
  value: string,
) => {
  if (value === toDateTimeInput((row as any)[field] ?? null)) return
  await updateTransaction(row.id, { [field]: toApiDateTime(value) }, field)
}

const handleTimestampChange = (
  row: TransactionRow,
  field: 'created_at' | 'updated_at',
  event: Event,
) => {
  const target = event.target as HTMLInputElement | null
  updateTransactionTimestamp(row, field, target?.value ?? '')
}
</script>

<template>
  <DataTable
    v-model:filters="filtersModel"
    :value="rows"
    filterDisplay="row"
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
      <div class="flex items-center justify-between gap-4">
        <TableTotalLabel label="Всего" :value="totalLabel" />
        <Button
          label="Сброс фильтров"
          text
          @click="emit('reset-filters')"
        />
      </div>
    </template>

    <Column
      :field="TRANSACTION_COLUMNS.id.field"
      :header="TRANSACTION_COLUMNS.id.header"
      :sortable="TRANSACTION_COLUMNS.id.sortable"
    >
      <template #filter="{ filterModel, filterCallback }">
        <InputText
          v-model="filterModel.value"
          class="w-full"
          @input="() => { filterCallback(); notifyParent(); }"
        />
      </template>
    </Column>

    <Column field="created_at" header="created_at" style="inline-size: 14rem;">
      <template #body="{ data }">
        <input
          type="datetime-local"
          class="finance-datetime-input"
          :value="toDateTimeInput(data.created_at)"
          :disabled="isSavingField(data.id, 'created_at')"
          @change="handleTimestampChange(data, 'created_at', $event)"
        >
      </template>
    </Column>

    <Column field="updated_at" header="updated_at" style="inline-size: 14rem;">
      <template #body="{ data }">
        <input
          type="datetime-local"
          class="finance-datetime-input"
          :value="toDateTimeInput(data.updated_at)"
          :disabled="isSavingField(data.id, 'updated_at')"
          @change="handleTimestampChange(data, 'updated_at', $event)"
        >
      </template>
    </Column>

    <Column
      :field="TRANSACTION_COLUMNS.isPaid.field"
      :header="TRANSACTION_COLUMNS.isPaid.header"
      :sortable="TRANSACTION_COLUMNS.isPaid.sortable"
    >
      <template #filter="{ filterModel, filterCallback }">
        <div class="flex items-center gap-2">
          <Select
            v-model="filterModel.value"
            :options="TRANSACTION_BOOLEAN_OPTIONS"
            optionLabel="label"
            optionValue="value"
            class="w-full"
            @change="() => { filterCallback(); notifyParent(); }"
          />
          <Button
            icon="pi pi-calendar"
            text
            @click="togglePanel(paidRangePanel, $event)"
          />
          <Popover ref="paidRangePanel">
            <div class="flex gap-2">
              <Calendar
                v-model="filtersModel.date_is_paid.value.from"
                placeholder="С"
                dateFormat="yy-mm-dd"
                @update:modelValue="() => { filterCallback(); notifyParent(); }"
              />
              <Calendar
                v-model="filtersModel.date_is_paid.value.to"
                placeholder="По"
                dateFormat="yy-mm-dd"
                @update:modelValue="() => { filterCallback(); notifyParent(); }"
              />
            </div>
          </Popover>
        </div>
      </template>
      <template #body="{ data }">
        <div class="leading-tight py-1">
          <Select
            :modelValue="Boolean(data.is_paid)"
            :options="rowBooleanOptions"
            optionLabel="label"
            optionValue="value"
            class="w-full"
            :disabled="isSavingField(data.id, 'is_paid')"
            @update:modelValue="updateBooleanField(data, 'is_paid', $event)"
          />
          <div class="status-date mt-1">
            {{ formatDateShort(data.date_is_paid) }}
          </div>
        </div>
      </template>
    </Column>

    <Column
      :field="TRANSACTION_COLUMNS.isCompleted.field"
      :header="TRANSACTION_COLUMNS.isCompleted.header"
      :sortable="TRANSACTION_COLUMNS.isCompleted.sortable"
    >
      <template #filter="{ filterModel, filterCallback }">
        <div class="flex items-center gap-2">
          <Select
            v-model="filterModel.value"
            :options="TRANSACTION_BOOLEAN_OPTIONS"
            optionLabel="label"
            optionValue="value"
            class="w-full"
            @change="() => { filterCallback(); notifyParent(); }"
          />
          <Button
            icon="pi pi-calendar"
            text
            @click="togglePanel(completedRangePanel, $event)"
          />
          <Popover ref="completedRangePanel">
            <div class="flex gap-2">
              <Calendar
                v-model="filtersModel.date_is_completed.value.from"
                placeholder="С"
                dateFormat="yy-mm-dd"
                @update:modelValue="() => { filterCallback(); notifyParent(); }"
              />
              <Calendar
                v-model="filtersModel.date_is_completed.value.to"
                placeholder="По"
                dateFormat="yy-mm-dd"
                @update:modelValue="() => { filterCallback(); notifyParent(); }"
              />
            </div>
          </Popover>
        </div>
      </template>
      <template #body="{ data }">
        <div class="leading-tight py-1">
          <Select
            :modelValue="Boolean(data.is_completed)"
            :options="rowBooleanOptions"
            optionLabel="label"
            optionValue="value"
            class="w-full"
            :disabled="isSavingField(data.id, 'is_completed')"
            @update:modelValue="updateBooleanField(data, 'is_completed', $event)"
          />
          <div class="status-date mt-1">
            {{ formatDateShort(data.date_is_completed) }}
          </div>
        </div>
      </template>
    </Column>

    <Column
      :field="TRANSACTION_COLUMNS.transactionType.field"
      :header="TRANSACTION_COLUMNS.transactionType.header"
    >
      <template #filter="{ filterModel, filterCallback }">
        <div class="flex items-center gap-2">
          <Select
            v-model="filterModel.value"
            :options="dictionaries.transactionTypes"
            optionLabel="name"
            optionValue="id"
            class="w-full"
            @change="() => { filterCallback(); notifyParent(); }"
          />
          <Button
            icon="pi pi-wallet"
            text
            @click="togglePanel(paymentMethodPanel, $event)"
          />
          <Popover ref="paymentMethodPanel">
            <Select
              v-model="filtersModel.payment_method_id.value"
              :options="dictionaries.paymentMethods"
              optionLabel="name"
              optionValue="id"
              class="w-full"
              @change="() => { filterCallback(); notifyParent(); }"
            />
          </Popover>
        </div>
      </template>
      <template #body="{ data }">
        <div class="whitespace-pre-line leading-tight py-1">
          {{
            [data.transaction_type?.name ?? '', data.payment_method?.name ?? '']
              .filter(Boolean)
              .join('\n')
          }}
        </div>
      </template>
    </Column>

    <Column
      :field="TRANSACTION_COLUMNS.contractOrCounterparty.field"
      :header="TRANSACTION_COLUMNS.contractOrCounterparty.header"
    >
      <template #filter="{ filterModel, filterCallback }">
        <InputText
          v-model="filterModel.value"
          class="w-full"
          @input="() => { filterCallback(); notifyParent(); }"
        />
      </template>
      <template #body="{ data }">
        <div class="whitespace-pre-line leading-tight py-1">
          {{
            [data.contract_id ? String(data.contract_id) : '', data.counterparty?.name ?? '']
              .filter(Boolean)
              .join('\n')
          }}
        </div>
      </template>
    </Column>

    <Column
      :field="TRANSACTION_COLUMNS.cashbox.field"
      :header="TRANSACTION_COLUMNS.cashbox.header"
      :style="{ inlineSize: cashboxInlineSize }"
      :headerStyle="{ inlineSize: cashboxInlineSize }"
      :bodyStyle="{ inlineSize: cashboxInlineSize }"
    >
      <template #filter="{ filterModel, filterCallback }">
        <Select
          v-model="filterModel.value"
          :options="dictionaries.cashBoxes"
          optionLabel="name"
          optionValue="id"
          class="w-full"
          @change="() => { filterCallback(); notifyParent(); }"
        />
      </template>
      <template #body="{ data }">
        <CashboxCell :cashbox="data.cashbox" size="sm" />
      </template>
    </Column>

    <Column
      :field="TRANSACTION_COLUMNS.sum.field"
      :header="TRANSACTION_COLUMNS.sum.header"
      :sortable="TRANSACTION_COLUMNS.sum.sortable"
    >
      <template #filter="{ filterCallback }">
        <Button
          icon="pi pi-sliders-h"
          text
          @click="togglePanel(sumRangePanel, $event)"
        />
        <Popover ref="sumRangePanel">
          <div class="flex gap-2">
            <InputNumber
              v-model="filtersModel.sum.value.min"
              placeholder="Мин"
              @update:modelValue="() => { filterCallback(); notifyParent(); }"
            />
            <InputNumber
              v-model="filtersModel.sum.value.max"
              placeholder="Макс"
              @update:modelValue="() => { filterCallback(); notifyParent(); }"
            />
          </div>
        </Popover>
      </template>
      <template #body="{ data }">
        {{ formatSum(data.sum) }}
      </template>
    </Column>

    <Column
      :field="TRANSACTION_COLUMNS.notes.field"
      :header="TRANSACTION_COLUMNS.notes.header"
    >
      <template #filter="{ filterModel, filterCallback }">
        <InputText
          v-model="filterModel.value"
          class="w-full"
          @input="() => { filterCallback(); notifyParent(); }"
        />
      </template>
    </Column>

    <Column
      :field="TRANSACTION_COLUMNS.related.field"
      :header="TRANSACTION_COLUMNS.related.header"
    >
      <template #filter="{ filterModel, filterCallback }">
        <InputText
          v-model="filterModel.value"
          class="w-full"
          @input="() => { filterCallback(); notifyParent(); }"
        />
      </template>
      <template #body="{ data }">
        {{ data.related_id ?? '' }}
      </template>
    </Column>

    <Column v-if="canDelete" header="" style="inline-size: 5ch;">
      <template #body="{ data }">
        <VTooltip location="top" content-class="text-body-2">
          <template #activator="{ props: tooltipProps }">
            <Button
              v-bind="tooltipProps"
              icon="pi pi-trash"
              text
              severity="danger"
              @click.stop="emit('delete', data)"
            />
          </template>
          <span>Удалить</span>
        </VTooltip>
      </template>
    </Column>

    <template #empty>
      <div class="text-center py-6 text-muted">Нет данных</div>
    </template>

    <template #loading>
      <div class="text-center py-6 text-muted">Загрузка...</div>
    </template>
  </DataTable>
</template>

<style scoped>
.whitespace-pre-line {
  white-space: pre-line;
}

.finance-datetime-input {
  width: 100%;
  min-height: 30px;
  border: 1px solid rgba(15, 23, 42, 0.15);
  border-radius: 6px;
  padding: 0.25rem 0.5rem;
  font-size: 0.8125rem;
  background: #fff;
}

.status-yes {
  color: #16a34a;
  font-weight: 600;
}

.status-no {
  color: #dc2626;
  font-weight: 600;
}

.status-null {
  color: #6b7280;
  font-weight: 600;
}

.status-date {
  color: #6b7280;
  font-size: 0.85em;
}
</style>

