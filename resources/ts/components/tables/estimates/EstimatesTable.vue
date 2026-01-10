<script setup lang="ts">
import { computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Calendar from 'primevue/calendar'
import Button from 'primevue/button'
import type { Estimate } from '@/types/estimates'

const EMPTY_TEXT = '—'

const props = defineProps<{
  rows: Estimate[]
  loading: boolean
  totalRecords: number
  scrollHeight: string
  virtualScrollerOptions: Record<string, any>
  search: string
  dateRange: (Date | null)[]
}>()

const emit = defineEmits<{
  (e: 'update:search', value: string): void
  (e: 'update:dateRange', value: (Date | null)[]): void
  (e: 'reset'): void
  (e: 'open', row: Estimate): void
  (e: 'delete', row: Estimate): void
  (e: 'create'): void
}>()

const searchModel = computed({
  get: () => props.search,
  set: value => emit('update:search', value),
})

const dateRangeModel = computed({
  get: () => props.dateRange,
  set: value => emit('update:dateRange', value),
})

const totalLabel = computed(() => Number(props.totalRecords ?? 0).toLocaleString('ru-RU'))
const hasFilters = computed(() => !!props.search || (props.dateRange?.length ?? 0) > 0)

const formatCurrency = (value?: number | null) =>
  typeof value === 'number'
    ? value.toLocaleString('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 })
    : EMPTY_TEXT

const formatDate = (value?: string | null) => {
  if (!value) return EMPTY_TEXT
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? EMPTY_TEXT : date.toLocaleDateString('ru-RU')
}
</script>

<template>
  <DataTable
    :value="rows"
    dataKey="id"
    class="p-datatable-sm"
    :loading="loading"
    :totalRecords="totalRecords"
    scrollable
    :scrollHeight="scrollHeight"
    :virtualScrollerOptions="virtualScrollerOptions"
    lazy
    stripedRows
  >
    <template #header>
      <div class="flex flex-column gap-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-2">
            <InputText
              v-model="searchModel"
              class="w-64"
              placeholder="Поиск по ID, клиенту, телефону, адресу"
            />
            <Calendar
              v-model="dateRangeModel"
              selectionMode="range"
              :manualInput="false"
              showIcon
              class="w-72"
              placeholder="Период создания"
            />
            <Button
              label="Сброс фильтров"
              size="small"
              text
              icon="pi pi-refresh"
              :disabled="!hasFilters"
              @click="emit('reset')"
            />
          </div>
          <div class="flex items-center gap-2">
            <TableTotalLabel label="Всего" :value="totalLabel" />
            <Button
              label="Новая смета"
              icon="pi pi-plus"
              size="small"
              @click="emit('create')"
            />
          </div>
        </div>
      </div>
    </template>

    <Column
      field="id"
      header="ID"
      sortable
      style="inline-size: 6ch;"
    />

    <Column field="client_name" header="Клиент">
      <template #body="{ data }">
        <div class="leading-tight">
          <div class="font-medium">{{ data.client_name ?? data.counterparty?.name ?? EMPTY_TEXT }}</div>
          <div class="text-xs text-muted">{{ data.client_phone ?? data.counterparty?.phone ?? EMPTY_TEXT }}</div>
        </div>
      </template>
    </Column>

    <Column field="site_address" header="Адрес участка">
      <template #body="{ data }">
        {{ data.site_address ?? EMPTY_TEXT }}
      </template>
    </Column>

    <Column field="creator" header="Создал" style="inline-size: 18ch;">
      <template #body="{ data }">
        {{ data.creator?.name ?? data.creator?.email ?? EMPTY_TEXT }}
      </template>
    </Column>

    <Column
      field="items_count"
      header="Позиций"
      style="inline-size: 10ch;"
    >
      <template #body="{ data }">
        {{ data.items_count ?? EMPTY_TEXT }}
      </template>
    </Column>

    <Column
      field="total_sum"
      header="Сумма"
      style="inline-size: 14ch;"
    >
      <template #body="{ data }">
        {{ formatCurrency(data.total_sum) }}
      </template>
    </Column>

    <Column
      field="created_at"
      header="Создана"
      sortable
      style="inline-size: 12ch;"
    >
      <template #body="{ data }">
        {{ formatDate(data.created_at) }}
      </template>
    </Column>

    <Column
      field="actions"
      header=""
      style="inline-size: 10ch;"
    >
      <template #body="{ data }">
        <div class="flex items-center gap-1">
          <Button
            icon="pi pi-external-link"
            text
            aria-label="Открыть смету"
            @click="emit('open', data)"
          />
          <Button
            icon="pi pi-trash"
            text
            severity="danger"
            aria-label="Удалить смету"
            @click="emit('delete', data)"
          />
        </div>
      </template>
    </Column>

    <template #empty>
      <div class="text-center py-6 text-muted">Нет данных.</div>
    </template>

    <template #loading>
      <div class="text-center py-6 text-muted">Загрузка...</div>
    </template>
  </DataTable>
</template>
