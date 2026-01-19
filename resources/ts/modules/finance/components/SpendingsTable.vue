<script setup lang="ts">
import { computed, ref, watch } from 'vue'
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
import { SPENDING_COLUMNS } from '@/modules/finance/config/spendingsTable.config'
import type { Spending } from '@/types/finance'

type SpendingRow = Spending & {
  fund_name?: string
  item_name?: string
  counterparty_name?: string
}

const props = defineProps<{
  rows: Spending[]
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

const filtersModel = computed({
  get: () => props.filters,
  set: value => emit('update:filters', value),
})

const totalLabel = computed(() => Number(props.totalRecords ?? 0).toLocaleString('ru-RU'))

const dictionaries = useDictionariesStore()
const dateRangePanel = ref<any>(null)
const sumRangePanel = ref<any>(null)

const fundMap = computed(
  () => new Map(dictionaries.spendingFunds.map(item => [String(item.id), item.name])),
)
const itemMap = computed(
  () => new Map(dictionaries.spendingItems.map(item => [String(item.id), item.name])),
)
const counterpartyMap = computed(
  () => new Map(dictionaries.counterparties.map(item => [String(item.id), item.name])),
)

const rows = computed<SpendingRow[]>(() =>
  (props.rows || []).map(row => ({
    ...row,
    fund_name:
      row.fund?.name ??
      (row.fond_id != null ? fundMap.value.get(String(row.fond_id)) : undefined) ??
      '',
    item_name:
      row.item?.name ??
      (row.spending_item_id != null
        ? itemMap.value.get(String(row.spending_item_id))
        : undefined) ??
      '',
    counterparty_name:
      row.counterparty?.name ??
      (row.counterparty_id != null
        ? counterpartyMap.value.get(String(row.counterparty_id))
        : undefined) ??
      '',
  })),
)

const cashboxInlineSize = computed(() => {
  const names = dictionaries.cashBoxes.map(item => item.name ?? '')
  const maxLength = names.reduce((max, name) => Math.max(max, name.length), 0)
  return `${Math.max(maxLength, 6)}ch`
})

const selectedFundId = computed(() => filtersModel.value?.fond_id?.value ?? null)
const spendingItemsForFund = computed(() => {
  const fundId = selectedFundId.value
  if (!fundId) return dictionaries.spendingItems
  const fundKey = String(fundId)
  return dictionaries.spendingItems.filter(item => String(item.fond_id ?? '') === fundKey)
})

watch(selectedFundId, () => {
  const currentItemId = filtersModel.value?.spending_item_id?.value ?? null
  if (!currentItemId) return
  const exists = spendingItemsForFund.value.some(item => String(item.id) === String(currentItemId))
  if (!exists && filtersModel.value?.spending_item_id) {
    filtersModel.value.spending_item_id.value = null
  }
})

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
      :field="SPENDING_COLUMNS.id.field"
      :header="SPENDING_COLUMNS.id.header"
      :sortable="SPENDING_COLUMNS.id.sortable"
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
      :field="SPENDING_COLUMNS.paymentDate.field"
      :header="SPENDING_COLUMNS.paymentDate.header"
      :sortable="SPENDING_COLUMNS.paymentDate.sortable"
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
      :field="SPENDING_COLUMNS.cashbox.field"
      :header="SPENDING_COLUMNS.cashbox.header"
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
        {{ data.cashbox?.name ?? '' }}
      </template>
    </Column>

    <Column
      :field="SPENDING_COLUMNS.sum.field"
      :header="SPENDING_COLUMNS.sum.header"
      :sortable="SPENDING_COLUMNS.sum.sortable"
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
      :field="SPENDING_COLUMNS.fund.field"
      :header="SPENDING_COLUMNS.fund.header"
      :showFilterMenu="false"
    >
      <template #filter="{ filterModel, filterCallback }">
        <Select
          v-model="filterModel.value"
          :options="dictionaries.spendingFunds"
          optionLabel="name"
          optionValue="id"
          class="w-full"
          @change="filterCallback()"
        />
      </template>
      <template #body="{ data }">
        {{ data.fund_name ?? '' }}
      </template>
    </Column>

    <Column
      :field="SPENDING_COLUMNS.item.field"
      :header="SPENDING_COLUMNS.item.header"
      :showFilterMenu="false"
    >
      <template #filter="{ filterModel, filterCallback }">
        <Select
          v-model="filterModel.value"
          :options="spendingItemsForFund"
          optionLabel="name"
          optionValue="id"
          class="w-full"
          @change="filterCallback()"
        />
      </template>
      <template #body="{ data }">
        {{ data.item_name ?? '' }}
      </template>
    </Column>

    <Column
      :field="SPENDING_COLUMNS.contractId.field"
      :header="SPENDING_COLUMNS.contractId.header"
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
      :field="SPENDING_COLUMNS.counterparty.field"
      :header="SPENDING_COLUMNS.counterparty.header"
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
      :field="SPENDING_COLUMNS.description.field"
      :header="SPENDING_COLUMNS.description.header"
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
