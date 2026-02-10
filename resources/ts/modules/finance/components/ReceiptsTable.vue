<script setup lang="ts">
import { computed, ref } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Calendar from 'primevue/calendar'
import Button from 'primevue/button'
import Popover from 'primevue/popover'
import { useDictionariesStore } from '@/stores/dictionaries'
import { formatDateShort, formatSum } from '@/utils/formatters/finance'
import { RECEIPT_COLUMNS } from '@/modules/finance/config/receiptsTable.config'
import { defaultReceiptFilters } from '@/modules/finance/composables/useReceiptFilters'
import CashboxCell from '@/components/cashboxes/CashboxCell.vue'
import type { Receipt } from '@/types/finance'

type ReceiptRow = Receipt & {
  counterparty_name?: string
  cashflow_name?: string
}

const props = defineProps<{
  rows: Receipt[]
  loading: boolean
  totalRecords: number
  scrollHeight: string
  virtualScrollerOptions: Record<string, any>
  filters: any
}>()

const emit = defineEmits<{
  (e: 'update:filters', value: any): void
  (e: 'sort', event: any): void
  (e: 'reset-filters'): void
}>()

const mergeFilters = (value: any) => {
  const base = defaultReceiptFilters()
  const source = value ?? {}

  return {
    ...base,
    ...source,
    payment_date: {
      ...base.payment_date,
      ...(source.payment_date ?? {}),
      value: {
        ...base.payment_date.value,
        ...(source.payment_date?.value ?? {}),
      },
    },
    sum: {
      ...base.sum,
      ...(source.sum ?? {}),
      value: {
        ...base.sum.value,
        ...(source.sum?.value ?? {}),
      },
    },
  }
}

const filtersModel = computed({
  get: () => mergeFilters(props.filters),
  set: value => emit('update:filters', mergeFilters(value)),
})

const totalLabel = computed(() => Number(props.totalRecords ?? 0).toLocaleString('ru-RU'))

const dictionaries = useDictionariesStore()
const dateRangePanel = ref<any>(null)
const sumRangePanel = ref<any>(null)

const counterpartyMap = computed(
  () => new Map(dictionaries.counterparties.map(item => [String(item.id), item.name])),
)

const cashflowMap = computed(() => {
  const map = new Map<string, string>()
  for (const item of dictionaries.cashflowItems) {
    map.set(String(item.id), item.name ?? '')
  }
  return map
})

const cashboxInlineSize = computed(() => {
  const names = dictionaries.cashBoxes.map(item => item.name ?? '')
  const maxLength = names.reduce((max, name) => Math.max(max, name.length), 0)
  const ch = Math.max(maxLength, 6)
  return `calc(${ch}ch + 56px)`
})

const rows = computed<ReceiptRow[]>(() =>
  (props.rows || []).map(row => {
    const fallbackId = row.contract?.counterparty_id ?? row.counterparty_id ?? null
    const fallbackKey = fallbackId != null ? String(fallbackId) : null
    const counterpartyName =
      row.counterparty?.name ??
      (fallbackKey ? counterpartyMap.value.get(fallbackKey) : undefined) ??
      ''

    return {
      ...row,
      counterparty_name: counterpartyName,
      cashflow_name:
        row.cashflow_item_id != null
          ? cashflowMap.value.get(String(row.cashflow_item_id)) ?? ''
          : '',
    }
  }),
)

const togglePanel = (panel: { toggle: (event: Event) => void } | null, event: Event) => {
  panel?.toggle(event)
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
      :field="RECEIPT_COLUMNS.id.field"
      :header="RECEIPT_COLUMNS.id.header"
      :showFilterMenu="false"
      style="inline-size: 6ch;"
    >
      <template #filter="{ filterModel, filterCallback }">
        <InputText
          v-model="filterModel.value"
          class="w-full"
          @input="filterCallback()"
        />
      </template>
    </Column>

    <Column
      :field="RECEIPT_COLUMNS.paymentDate.field"
      :header="RECEIPT_COLUMNS.paymentDate.header"
      :sortable="RECEIPT_COLUMNS.paymentDate.sortable"
      :showFilterMenu="false"
      style="inline-size: 10ch;"
    >
      <template #filter="{ filterCallback }">
        <Button
          icon="pi pi-calendar"
          text
          @click="togglePanel(dateRangePanel, $event)"
        />
        <Popover ref="dateRangePanel">
          <div class="flex gap-2">
            <Calendar
              v-model="filtersModel.payment_date.value.from"
              placeholder="С"
              dateFormat="yy-mm-dd"
              @update:modelValue="filterCallback()"
            />
            <Calendar
              v-model="filtersModel.payment_date.value.to"
              placeholder="По"
              dateFormat="yy-mm-dd"
              @update:modelValue="filterCallback()"
            />
          </div>
        </Popover>
      </template>
      <template #body="{ data }">
        {{ formatDateShort(data.payment_date) }}
      </template>
    </Column>

    <Column
      :field="RECEIPT_COLUMNS.cashbox.field"
      :header="RECEIPT_COLUMNS.cashbox.header"
      :showFilterMenu="false"
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
          :style="{ inlineSize: cashboxInlineSize }"
          @change="filterCallback()"
        />
      </template>
      <template #body="{ data }">
        <CashboxCell :cashbox="data.cashbox" size="sm" />
      </template>
    </Column>

    <Column
      :field="RECEIPT_COLUMNS.cashflow.field"
      :header="RECEIPT_COLUMNS.cashflow.header"
      :showFilterMenu="false"
    >
      <template #filter="{ filterModel, filterCallback }">
        <Select
          v-model="filterModel.value"
          :options="dictionaries.cashflowItems.filter(item => item.direction === 'IN')"
          optionLabel="name"
          optionValue="id"
          class="w-full"
          @change="filterCallback()"
        />
      </template>
      <template #body="{ data }">
        {{ data.cashflow_name ?? '' }}
      </template>
    </Column>

    <Column
      :field="RECEIPT_COLUMNS.sum.field"
      :header="RECEIPT_COLUMNS.sum.header"
      :sortable="RECEIPT_COLUMNS.sum.sortable"
      :showFilterMenu="false"
      style="inline-size: 10ch;"
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
              @update:modelValue="filterCallback()"
            />
            <InputNumber
              v-model="filtersModel.sum.value.max"
              placeholder="Макс"
              @update:modelValue="filterCallback()"
            />
          </div>
        </Popover>
      </template>
      <template #body="{ data }">
        {{ formatSum(data.sum) }}
      </template>
    </Column>

    <Column
      :field="RECEIPT_COLUMNS.contractId.field"
      :header="RECEIPT_COLUMNS.contractId.header"
      :showFilterMenu="false"
      style="inline-size: 7ch;"
      headerStyle="inline-size: 7ch;"
      bodyStyle="inline-size: 7ch;"
    >
      <template #filter="{ filterModel, filterCallback }">
        <InputNumber
          v-model="filterModel.value"
          :style="{ inlineSize: '7ch' }"
          :inputStyle="{ inlineSize: '7ch' }"
          @update:modelValue="filterCallback()"
        />
      </template>
      <template #body="{ data }">
        {{ data.contract_id ?? '' }}
      </template>
    </Column>

    <Column
      :field="RECEIPT_COLUMNS.counterparty.field"
      :header="RECEIPT_COLUMNS.counterparty.header"
      :showFilterMenu="false"
    >
      <template #filter="{ filterModel, filterCallback }">
        <InputText
          v-model="filterModel.value"
          class="w-full"
          @input="filterCallback()"
        />
      </template>
      <template #body="{ data }">
        {{ data.counterparty_name ?? '' }}
      </template>
    </Column>

    <Column
      :field="RECEIPT_COLUMNS.description.field"
      :header="RECEIPT_COLUMNS.description.header"
      :showFilterMenu="false"
    >
      <template #filter="{ filterModel, filterCallback }">
        <InputText
          v-model="filterModel.value"
          class="w-full"
          @input="filterCallback()"
        />
      </template>
      <template #body="{ data }">
        {{ data.description ?? '' }}
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
