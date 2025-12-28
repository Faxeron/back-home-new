<script setup lang="ts">
import { computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Button from 'primevue/button'
import { CONTRACT_COLUMNS } from '@/config/tables/contracts'
import { formatDateShort, formatSum } from '@/utils/formatters/finance'
import type { Contract, ContractStatus } from '@/types/finance'

const EMPTY_TEXT = '\u2014'

const props = defineProps<{
  rows: Contract[]
  loading: boolean
  totalRecords: number
  scrollHeight: string
  virtualScrollerOptions: Record<string, any>
  search: string
  statusId: number | null
  statuses: ContractStatus[]
}>()

const emit = defineEmits<{
  (e: 'update:search', value: string): void
  (e: 'update:statusId', value: number | null): void
  (e: 'status-change', payload: { row: Contract; statusId: number | null }): void
  (e: 'action', payload: { action: string; row: Contract }): void
  (e: 'reset'): void
}>()

const searchModel = computed({
  get: () => props.search,
  set: value => emit('update:search', value),
})

const totalLabel = computed(() => Number(props.totalRecords ?? 0).toLocaleString('ru-RU'))
const hasFilters = computed(() => !!props.search || props.statusId !== null)

const statusMap = computed(() => {
  const map = new Map<number, ContractStatus>()
  for (const status of props.statuses || []) {
    if (status?.id != null) map.set(Number(status.id), status)
  }
  return map
})

const formatMoney = (value?: number | null) => {
  if (value === null || value === undefined) return EMPTY_TEXT
  return formatSum(value)
}

const getStatus = (statusId?: number | null) => {
  if (statusId === null || statusId === undefined) return null
  return statusMap.value.get(Number(statusId)) ?? null
}

const getStatusColor = (statusId?: number | null) => getStatus(statusId)?.color ?? '#94a3b8'

const getTextColor = (hexColor: string) => {
  const hex = hexColor.replace('#', '')
  if (hex.length !== 6) return '#111827'
  const r = parseInt(hex.slice(0, 2), 16)
  const g = parseInt(hex.slice(2, 4), 16)
  const b = parseInt(hex.slice(4, 6), 16)
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255
  return luminance > 0.6 ? '#111827' : '#ffffff'
}

const statusButtonStyle = (status: ContractStatus, isActive: boolean) => {
  const color = status.color ?? '#94a3b8'
  return isActive
    ? {
        backgroundColor: color,
        borderColor: color,
        color: getTextColor(color),
      }
    : {
        borderColor: color,
        color,
      }
}

const handleStatusToggle = (nextId: number) => {
  const nextValue = props.statusId === nextId ? null : nextId
  emit('update:statusId', nextValue)
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
      <div class="flex flex-col gap-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <InputText
              v-model="searchModel"
              class="w-64"
              placeholder="Поиск по ID, клиенту, модели"
            />
            <Button
              v-if="searchModel"
              icon="pi pi-times"
              text
              aria-label="Очистить поиск"
              @click="emit('update:search', '')"
            />
          </div>
          <div class="text-sm text-muted">Всего: {{ totalLabel }}</div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <div
            v-if="statuses?.length"
            class="text-xs text-muted"
          >
            Статусы:
          </div>
          <Button
            v-for="status in statuses"
            :key="status.id"
            :label="status.name"
            size="small"
            :outlined="statusId !== Number(status.id)"
            :style="statusButtonStyle(status, statusId === Number(status.id))"
            @click="handleStatusToggle(Number(status.id))"
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
      </div>
    </template>

    <Column
      :field="CONTRACT_COLUMNS.id.field"
      :header="CONTRACT_COLUMNS.id.header"
      style="inline-size: 6ch;"
    >
      <template #body="{ data }">
        {{ data.id ?? EMPTY_TEXT }}
      </template>
    </Column>

    <Column
      :field="CONTRACT_COLUMNS.counterparty.field"
      :header="CONTRACT_COLUMNS.counterparty.header"
    >
      <template #body="{ data }">
        {{ data.counterparty?.name ?? EMPTY_TEXT }}
      </template>
    </Column>

    <Column
      :field="CONTRACT_COLUMNS.address.field"
      :header="CONTRACT_COLUMNS.address.header"
    >
      <template #body="{ data }">
        {{ data.address ?? EMPTY_TEXT }}
      </template>
    </Column>

    <Column
      :field="CONTRACT_COLUMNS.model.field"
      :header="CONTRACT_COLUMNS.model.header"
    >
      <template #body>
        {{ EMPTY_TEXT }}
      </template>
    </Column>

    <Column
      :field="CONTRACT_COLUMNS.estimate.field"
      :header="CONTRACT_COLUMNS.estimate.header"
    >
      <template #body>
        {{ EMPTY_TEXT }}
      </template>
    </Column>

    <Column
      :field="CONTRACT_COLUMNS.workDates.field"
      :header="CONTRACT_COLUMNS.workDates.header"
    >
      <template #body="{ data }">
        <div class="leading-tight py-1">
          <div>{{ formatDateShort(data.work_start_date) }}</div>
          <div class="text-muted text-xs">{{ formatDateShort(data.work_end_date) }}</div>
        </div>
      </template>
    </Column>

    <Column
      :field="CONTRACT_COLUMNS.saleType.field"
      :header="CONTRACT_COLUMNS.saleType.header"
    >
      <template #body="{ data }">
        {{ data.sale_type?.name ?? EMPTY_TEXT }}
      </template>
    </Column>

    <Column
      :field="CONTRACT_COLUMNS.totalAmount.field"
      :header="CONTRACT_COLUMNS.totalAmount.header"
    >
      <template #body="{ data }">
        {{ formatMoney(data.total_amount) }}
      </template>
    </Column>

    <Column
      :field="CONTRACT_COLUMNS.paidDebt.field"
      :header="CONTRACT_COLUMNS.paidDebt.header"
    >
      <template #body="{ data }">
        <div class="leading-tight py-1">
          <div>{{ formatMoney(data.paid_amount) }}</div>
          <div class="text-muted text-xs">{{ formatMoney(data.debt) }}</div>
        </div>
      </template>
    </Column>

    <Column
      :field="CONTRACT_COLUMNS.staff.field"
      :header="CONTRACT_COLUMNS.staff.header"
    >
      <template #body="{ data }">
        <div class="leading-tight py-1">
          <div>{{ data.manager?.name ?? EMPTY_TEXT }}</div>
          <div class="text-muted text-xs">{{ data.measurer?.name ?? EMPTY_TEXT }}</div>
        </div>
      </template>
    </Column>

    <Column
      :field="CONTRACT_COLUMNS.status.field"
      :header="CONTRACT_COLUMNS.status.header"
    >
      <template #body="{ data }">
        <Select
          :modelValue="data.contract_status_id ?? null"
          :options="statuses"
          optionLabel="name"
          optionValue="id"
          class="w-full"
          @update:modelValue="
            emit('status-change', { row: data, statusId: $event != null ? Number($event) : null })
          "
        >
          <template #value="{ value }">
            <div
              v-if="value"
              class="flex items-center gap-2"
            >
              <span
                class="status-dot"
                :style="{ backgroundColor: getStatusColor(value) }"
              />
              <span>{{ getStatus(value)?.name ?? EMPTY_TEXT }}</span>
            </div>
            <span v-else class="text-muted">{{ EMPTY_TEXT }}</span>
          </template>
          <template #option="{ option }">
            <div class="flex items-center gap-2">
              <span
                class="status-dot"
                :style="{ backgroundColor: option?.color ?? '#94a3b8' }"
              />
              <span>{{ option?.name ?? '' }}</span>
            </div>
          </template>
        </Select>
      </template>
    </Column>

    <Column
      :field="CONTRACT_COLUMNS.actions.field"
      :header="CONTRACT_COLUMNS.actions.header"
      style="inline-size: 14ch;"
    >
      <template #body="{ data }">
        <div class="flex items-center gap-1">
          <Button
            icon="pi pi-file"
            text
            aria-label="Договор"
            @click="emit('action', { action: 'contract', row: data })"
          />
          <Button
            icon="pi pi-file-edit"
            text
            aria-label="Акт"
            @click="emit('action', { action: 'act', row: data })"
          />
          <Button
            icon="pi pi-plus"
            text
            aria-label="Приход"
            @click="emit('action', { action: 'receipt', row: data })"
          />
          <Button
            icon="pi pi-minus"
            text
            aria-label="Расход"
            @click="emit('action', { action: 'spending', row: data })"
          />
          <Button
            icon="pi pi-pencil"
            text
            aria-label="Редактировать"
            @click="emit('action', { action: 'edit', row: data })"
          />
          <Button
            icon="pi pi-trash"
            text
            aria-label="Удалить"
            @click="emit('action', { action: 'delete', row: data })"
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

<style scoped>
.status-dot {
  inline-size: 0.65rem;
  block-size: 0.65rem;
  border-radius: 999px;
  display: inline-block;
}
</style>
