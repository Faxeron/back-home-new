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
import { formatSum, statusLines } from '@/utils/formatters/finance'
import { TRANSACTION_BOOLEAN_OPTIONS, TRANSACTION_COLUMNS } from '@/modules/finance/config/transactionsTable.config'
import CashboxBadge from '@/components/cashboxes/CashboxBadge.vue'
import type { Transaction } from '@/types/finance'

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
}>()

const filtersModel = computed({
  get: () => props.filters,
  set: value => emit('update:filters', value),
})

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
          @input="filterCallback()"
        />
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
            @change="filterCallback()"
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
                @update:modelValue="filterCallback()"
              />
              <Calendar
                v-model="filtersModel.date_is_paid.value.to"
                placeholder="По"
                dateFormat="yy-mm-dd"
                @update:modelValue="filterCallback()"
              />
            </div>
          </Popover>
        </div>
      </template>
      <template #body="{ data }">
        <div class="leading-tight py-1">
          <div
            v-for="(line, index) in statusLines(data.is_paid, data.date_is_paid)"
            :key="index"
            :class="line.className"
          >
            {{ line.text }}
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
            @change="filterCallback()"
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
                @update:modelValue="filterCallback()"
              />
              <Calendar
                v-model="filtersModel.date_is_completed.value.to"
                placeholder="По"
                dateFormat="yy-mm-dd"
                @update:modelValue="filterCallback()"
              />
            </div>
          </Popover>
        </div>
      </template>
      <template #body="{ data }">
        <div class="leading-tight py-1">
          <div
            v-for="(line, index) in statusLines(data.is_completed, data.date_is_completed)"
            :key="index"
            :class="line.className"
          >
            {{ line.text }}
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
            @change="filterCallback()"
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
              @change="filterCallback()"
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
          @input="filterCallback()"
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
          @change="filterCallback()"
        />
      </template>
      <template #body="{ data }">
        <CashboxBadge :cashbox="data.cashbox" size="sm" />
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
      :field="TRANSACTION_COLUMNS.notes.field"
      :header="TRANSACTION_COLUMNS.notes.header"
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
      :field="TRANSACTION_COLUMNS.related.field"
      :header="TRANSACTION_COLUMNS.related.header"
    >
      <template #filter="{ filterModel, filterCallback }">
        <InputText
          v-model="filterModel.value"
          class="w-full"
          @input="filterCallback()"
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
