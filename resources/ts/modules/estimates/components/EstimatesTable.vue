<script setup lang="ts">
import { computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Calendar from 'primevue/calendar'
import Button from 'primevue/button'
import type { Estimate } from '@/modules/estimates/types/estimates.types'
import {
  ESTIMATE_LIST_HEADERS,
  ESTIMATE_LIST_LABELS,
  EMPTY_TEXT,
  formatDate,
} from '@/modules/estimates/config/estimatesList.config'
import { formatCurrency } from '@/modules/estimates/config/estimateTable.config'

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
  (e: 'create-contract', row: Estimate): void
  (e: 'open-contract', row: Estimate): void
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
              :placeholder="ESTIMATE_LIST_LABELS.searchPlaceholder"
            />
            <Calendar
              v-model="dateRangeModel"
              selectionMode="range"
              :manualInput="false"
              showIcon
              class="w-72"
              :placeholder="ESTIMATE_LIST_LABELS.datePlaceholder"
            />
            <Button
              :label="ESTIMATE_LIST_LABELS.resetFilters"
              size="small"
              text
              icon="pi pi-refresh"
              :disabled="!hasFilters"
              @click="emit('reset')"
            />
          </div>
          <div class="flex items-center gap-2">
            <TableTotalLabel :label="ESTIMATE_LIST_LABELS.total" :value="totalLabel" />
            <Button
              :label="ESTIMATE_LIST_LABELS.create"
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
      :header="ESTIMATE_LIST_HEADERS.id"
      sortable
      style="inline-size: 6ch;"
    />

    <Column field="client_name" :header="ESTIMATE_LIST_HEADERS.client">
      <template #body="{ data }">
        <div class="leading-tight">
          <div class="font-medium">{{ data.client_name ?? data.counterparty?.name ?? EMPTY_TEXT }}</div>
          <div class="text-xs text-muted">{{ data.client_phone ?? data.counterparty?.phone ?? EMPTY_TEXT }}</div>
        </div>
      </template>
    </Column>

    <Column field="site_address" :header="ESTIMATE_LIST_HEADERS.siteAddress">
      <template #body="{ data }">
        {{ data.site_address ?? EMPTY_TEXT }}
      </template>
    </Column>

    <Column field="creator" :header="ESTIMATE_LIST_HEADERS.creator" style="inline-size: 18ch;">
      <template #body="{ data }">
        {{ data.creator?.name ?? data.creator?.email ?? EMPTY_TEXT }}
      </template>
    </Column>

    <Column
      field="items_count"
      :header="ESTIMATE_LIST_HEADERS.itemsCount"
      style="inline-size: 10ch;"
    >
      <template #body="{ data }">
        {{ data.items_count ?? EMPTY_TEXT }}
      </template>
    </Column>

    <Column
      field="total_sum"
      :header="ESTIMATE_LIST_HEADERS.totalSum"
      style="inline-size: 14ch;"
    >
      <template #body="{ data }">
        {{ formatCurrency(data.total_sum) }}
      </template>
    </Column>

    <Column
      field="created_at"
      :header="ESTIMATE_LIST_HEADERS.createdAt"
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
      style="inline-size: 12ch;"
    >
      <template #body="{ data }">
        <div class="flex items-center gap-1">
          <Button
            icon="pi pi-file-edit"
            text
            :disabled="Boolean(data.contract_id)"
            aria-label="Create contract"
            @click="emit('create-contract', data)"
          />
          <Button
            v-if="data.contract_id"
            icon="pi pi-eye"
            text
            aria-label="Open contract"
            @click="emit('open-contract', data)"
          />
          <Button
            icon="pi pi-external-link"
            text
            :aria-label="ESTIMATE_LIST_LABELS.openAria"
            @click="emit('open', data)"
          />
          <Button
            icon="pi pi-trash"
            text
            severity="danger"
            :aria-label="ESTIMATE_LIST_LABELS.deleteAria"
            @click="emit('delete', data)"
          />
        </div>
      </template>
    </Column>

    <template #empty>
      <div class="text-center py-6 text-muted">{{ ESTIMATE_LIST_LABELS.empty }}</div>
    </template>

    <template #loading>
      <div class="text-center py-6 text-muted">{{ ESTIMATE_LIST_LABELS.loading }}</div>
    </template>
  </DataTable>
</template>
