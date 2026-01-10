<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Calendar from 'primevue/calendar'
import Button from 'primevue/button'
import { useTableInfinite } from '@/composables/useTableLazy'
import type { ContractStatusChange } from '@/types/finance'

const tableRef = ref<any>(null)
const scrollHeight = ref('700px')
const contractId = ref('')
const dateFrom = ref<Date | null>(null)
const dateTo = ref<Date | null>(null)

const formatDate = (value: Date | null) => {
  if (!value) return null
  return value.toISOString().slice(0, 10)
}

const params = computed(() => {
  const result: Record<string, any> = {}
  const id = contractId.value.trim()
  if (id) result.contract_id = id
  const from = formatDate(dateFrom.value)
  const to = formatDate(dateTo.value)
  if (from) result.date_from = from
  if (to) result.date_to = to
  return result
})

const {
  data,
  total,
  loading,
  reset,
  virtualScrollerOptions,
} = useTableInfinite<ContractStatusChange>({
  endpoint: 'contracts/status-history',
  perPage: 200,
  rowHeight: 52,
  params: () => params.value,
})

const totalLabel = computed(() => Number(total.value ?? 0).toLocaleString('ru-RU'))

const updateScrollHeight = () => {
  const tableEl = tableRef.value?.$el as HTMLElement | undefined
  if (!tableEl) return
  const rect = tableEl.getBoundingClientRect()
  const padding = 24
  const nextHeight = Math.max(320, window.innerHeight - rect.top - padding)
  scrollHeight.value = `${Math.floor(nextHeight)}px`
}

const handleResize = () => {
  updateScrollHeight()
}

const applyFilters = () => {
  reset()
}

const resetFilters = () => {
  contractId.value = ''
  dateFrom.value = null
  dateTo.value = null
}

watch([contractId, dateFrom, dateTo], () => {
  applyFilters()
})

onMounted(async () => {
  await reset()
  await nextTick()
  updateScrollHeight()
  window.addEventListener('resize', handleResize)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
})
</script>

<template>
  <div class="flex flex-column gap-3">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2">
        <InputText
          v-model="contractId"
          class="w-64"
          placeholder="ID договора"
        />
        <Calendar
          v-model="dateFrom"
          placeholder="Дата с"
          dateFormat="yy-mm-dd"
          showIcon
        />
        <Calendar
          v-model="dateTo"
          placeholder="Дата по"
          dateFormat="yy-mm-dd"
          showIcon
        />
        <Button
          label="Сбросить"
          text
          icon="pi pi-refresh"
          @click="resetFilters"
        />
      </div>
      <TableTotalLabel label="Всего" :value="totalLabel" />
    </div>

    <DataTable
      ref="tableRef"
      :value="data"
      dataKey="id"
      class="p-datatable-sm"
      :loading="loading"
      :totalRecords="total"
      scrollable
      :scrollHeight="scrollHeight"
      :virtualScrollerOptions="virtualScrollerOptions"
      lazy
      stripedRows
    >
      <Column field="changed_at" header="Дата" style="inline-size: 16ch;">
        <template #body="{ data: row }">
          {{ row.changed_at?.slice(0, 19).replace('T', ' ') ?? '—' }}
        </template>
      </Column>
      <Column field="contract_id" header="Договор" style="inline-size: 10ch;">
        <template #body="{ data: row }">
          {{ row.contract_id ?? '—' }}
        </template>
      </Column>
      <Column field="previous_status" header="Было">
        <template #body="{ data: row }">
          {{ row.previous_status?.name ?? '—' }}
        </template>
      </Column>
      <Column field="new_status" header="Стало">
        <template #body="{ data: row }">
          {{ row.new_status?.name ?? '—' }}
        </template>
      </Column>
      <Column field="changed_by" header="Кем">
        <template #body="{ data: row }">
          {{ row.changed_by?.name ?? row.changed_by?.email ?? '—' }}
        </template>
      </Column>

      <template #empty>
        <div class="text-center py-6 text-muted">Нет данных.</div>
      </template>
      <template #loading>
        <div class="text-center py-6 text-muted">Загрузка...</div>
      </template>
    </DataTable>
  </div>
</template>
