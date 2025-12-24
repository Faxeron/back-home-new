<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { FilterService } from '@primevue/core/api'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Calendar from 'primevue/calendar'
import Button from 'primevue/button'
import Popover from 'primevue/popover'
import { $api } from '@/utils/api'
import { useDictionariesStore } from '@/stores/dictionaries'
import type { Transaction } from '@/types/finance'

type TransactionRow = Transaction & {
  contract_or_counterparty?: string
  related?: string
}

const dictionaries = useDictionariesStore()

const BOOLEAN_OPTIONS = [
  { label: 'Все', value: null },
  { label: 'ДА', value: true },
  { label: 'НЕТ', value: false },
]

const INCLUDE = 'cashbox,counterparty,contract,transactionType,paymentMethod'
const FETCH_LIMIT = 1000

const defaultFilters = () => ({
  id: { value: '', matchMode: 'contains' },
  is_paid: { value: null, matchMode: 'equals' },
  date_is_paid: { value: { from: null, to: null }, matchMode: 'custom' },
  is_completed: { value: null, matchMode: 'equals' },
  date_is_completed: { value: { from: null, to: null }, matchMode: 'custom' },
  transaction_type_id: { value: null, matchMode: 'equals' },
  payment_method_id: { value: null, matchMode: 'equals' },
  contract_or_counterparty: { value: '', matchMode: 'custom' },
  cashbox_id: { value: null, matchMode: 'equals' },
  sum: { value: { min: null, max: null }, matchMode: 'custom' },
  notes: { value: '', matchMode: 'contains' },
  related: { value: '', matchMode: 'contains' },
})

const filters = ref(defaultFilters())
const data = ref<Transaction[]>([])
const loading = ref(false)

const paidRangePanel = ref<any>(null)
const completedRangePanel = ref<any>(null)
const paymentMethodPanel = ref<any>(null)
const sumRangePanel = ref<any>(null)

const rows = computed<TransactionRow[]>(() =>
  (data.value || []).map(row => ({
    ...row,
    contract_or_counterparty: [row.contract_id, row.counterparty?.name].filter(Boolean).join(' '),
    related: row.related_id != null ? String(row.related_id) : '',
  })),
)

const resetFilters = () => {
  filters.value = defaultFilters()
}

const togglePanel = (panel: { toggle: (event: Event) => void } | null, event: Event) => {
  panel?.toggle(event)
}

const toDate = (value: unknown) => {
  if (!value) return null
  const date = value instanceof Date ? value : new Date(value as string)
  return Number.isNaN(date.getTime()) ? null : date
}

const startOfDay = (value: Date) => new Date(value.getFullYear(), value.getMonth(), value.getDate())
const endOfDay = (value: Date) =>
  new Date(value.getFullYear(), value.getMonth(), value.getDate(), 23, 59, 59, 999)

const containsFilter = (value: any, filter: any, locale?: string) => {
  if (filter === undefined || filter === null || filter === '') return true
  if (value === undefined || value === null) return false
  const filterValue = String(filter).toLocaleLowerCase(locale)
  const stringValue = String(value).toLocaleLowerCase(locale)
  return stringValue.includes(filterValue)
}

FilterService.register('custom', (value: any, filter: any, locale?: string) => {
  if (filter === undefined || filter === null || filter === '') return true

  if (typeof filter === 'object') {
    if ('from' in filter || 'to' in filter) {
      const from = toDate(filter.from)
      const to = toDate(filter.to)
      if (!from && !to) return true
      const current = toDate(value)
      if (!current) return false
      if (from && current < startOfDay(from)) return false
      if (to && current > endOfDay(to)) return false
      return true
    }

    if ('min' in filter || 'max' in filter) {
      const min = filter.min ?? null
      const max = filter.max ?? null
      if (min === null && max === null) return true
      const amountValue = typeof value === 'object' ? value?.amount ?? value : value
      const amount = Number(amountValue)
      if (!Number.isFinite(amount)) return false
      if (min !== null && amount < min) return false
      if (max !== null && amount > max) return false
      return true
    }
  }

  return containsFilter(value ?? '', filter, locale)
})

const formatDateShort = (value?: unknown) => {
  if (!value) return '\u2014'
  const raw = String(value)
  const datePart = raw.split('T')[0]
  const parts = datePart.split('-')
  if (parts.length === 3 && parts[0].length === 4) {
    const [year, month, day] = parts
    if (year && month && day) return `${day}.${month}.${year}`
  }
  const date = value instanceof Date ? value : new Date(String(value))
  if (Number.isNaN(date.getTime())) return '\u2014'
  const day = String(date.getDate()).padStart(2, '0')
  const month = String(date.getMonth() + 1).padStart(2, '0')
  return `${day}.${month}.${date.getFullYear()}`
}

const statusLines = (flag?: boolean | null, date?: string | null) => {
  const status = flag === true ? 'ДА' : flag === false ? 'НЕТ' : '\u2014'
  const statusClass = flag === true ? 'status-yes' : flag === false ? 'status-no' : 'status-null'
  return [
    { text: status, className: statusClass },
    { text: formatDateShort(date), className: 'status-date' },
  ]
}

const formatSum = (sum: any) => {
  const amountValue = typeof sum === 'object' ? sum?.amount ?? sum : sum
  const amount = Number(amountValue)
  if (!Number.isFinite(amount)) return amountValue ?? ''
  return amount.toLocaleString('ru-RU')
}

const load = async () => {
  loading.value = true
  try {
    const res: any = await $api('finance/transactions', {
      params: { include: INCLUDE, per_page: FETCH_LIMIT },
    })
    const list = Array.isArray(res?.data?.data)
      ? res.data.data
      : Array.isArray(res?.data)
        ? res.data
        : Array.isArray(res)
          ? res
          : []
    data.value = list
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await Promise.all([
    dictionaries.loadCashBoxes(true),
    dictionaries.loadTransactionTypes(),
    dictionaries.loadPaymentMethods(),
  ])
  await load()
})
</script>

<template>
  <DataTable
    v-model:filters="filters"
    :value="rows"
    filterDisplay="row"
    paginator
    :rows="25"
    :rowsPerPageOptions="[25, 50, 100]"
    dataKey="id"
    class="p-datatable-sm"
    :loading="loading"
  >
    <template #header>
      <div class="flex items-center justify-end">
        <Button
          label="Сброс фильтров"
          text
          @click="resetFilters"
        />
      </div>
    </template>

    <Column
      field="id"
      header="ID"
      sortable
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
      field="is_paid"
      header="Оплачено"
      sortable
    >
      <template #filter="{ filterModel, filterCallback }">
        <div class="flex items-center gap-2">
          <Select
            v-model="filterModel.value"
            :options="BOOLEAN_OPTIONS"
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
                v-model="filters.date_is_paid.value.from"
                placeholder="С"
                dateFormat="yy-mm-dd"
                @update:modelValue="filterCallback()"
              />
              <Calendar
                v-model="filters.date_is_paid.value.to"
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
      field="is_completed"
      header="Исполнено"
      sortable
    >
      <template #filter="{ filterModel, filterCallback }">
        <div class="flex items-center gap-2">
          <Select
            v-model="filterModel.value"
            :options="BOOLEAN_OPTIONS"
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
                v-model="filters.date_is_completed.value.from"
                placeholder="С"
                dateFormat="yy-mm-dd"
                @update:modelValue="filterCallback()"
              />
              <Calendar
                v-model="filters.date_is_completed.value.to"
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
      field="transaction_type_id"
      header="Тип / Метод платежа"
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
              v-model="filters.payment_method_id.value"
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
            [
              data.transaction_type?.name ?? '',
              data.payment_method?.name ?? '',
            ]
              .filter(Boolean)
              .join('\n')
          }}
        </div>
      </template>
    </Column>

    <Column
      field="contract_or_counterparty"
      header="Договор / Контрагент"
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
            [
              data.contract_id ? String(data.contract_id) : '',
              data.counterparty?.name ?? '',
            ]
              .filter(Boolean)
              .join('\n')
          }}
        </div>
      </template>
    </Column>

    <Column
      field="cashbox_id"
      header="Касса"
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
        {{ data.cashbox?.name ?? '' }}
      </template>
    </Column>

    <Column
      field="sum"
      header="Сумма"
      sortable
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
              v-model="filters.sum.value.min"
              placeholder="Мин"
              @update:modelValue="filterCallback()"
            />
            <InputNumber
              v-model="filters.sum.value.max"
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
      field="notes"
      header="Комментарий"
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
      field="related"
      header="Связь"
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
