<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import type { InstallationRow } from '../../types/installations.types'

const props = defineProps<{
  rows: InstallationRow[]
  loading?: boolean
  totalRecords: number
  scrollHeight: string
  virtualScrollerOptions: Record<string, any>
}>()

const emit = defineEmits<{
  (e: 'assign', row: InstallationRow): void
}>()

const router = useRouter()

const statusSeverity = (status: InstallationRow['status']) => {
  if (status === 'completed') return 'success'
  if (status === 'assigned') return 'warning'
  return 'secondary'
}

const hasRows = computed(() => props.rows?.length > 0)
</script>

<template>
  <DataTable
    :value="rows"
    dataKey="contract_id"
    class="p-datatable-sm"
    :loading="loading"
    :totalRecords="totalRecords"
    scrollable
    :scrollHeight="scrollHeight"
    :virtualScrollerOptions="virtualScrollerOptions"
    :sortField="'contract_id'"
    :sortOrder="-1"
    lazy
    stripedRows
  >
    <Column field="contract_id" header="Договор" style="inline-size: 10ch;">
      <template #body="{ data }">
        <span>{{ data.contract_id ?? '—' }}</span>
      </template>
    </Column>
    <Column field="counterparty_name" header="ФИО заказчика">
      <template #body="{ data }">
        <span>{{ data.counterparty_name ?? '—' }}</span>
      </template>
    </Column>
    <Column field="address" header="Адрес монтажа">
      <template #body="{ data }">
        <span>{{ data.address ?? '—' }}</span>
      </template>
    </Column>
    <Column field="work_start_date" header="Дата от" style="inline-size: 14ch;">
      <template #body="{ data }">
        <span>{{ data.work_start_date ?? '—' }}</span>
      </template>
    </Column>
    <Column field="work_end_date" header="Дата до" style="inline-size: 14ch;">
      <template #body="{ data }">
        <span>{{ data.work_end_date ?? '—' }}</span>
      </template>
    </Column>
    <Column field="work_done_date" header="Фактическая дата" style="inline-size: 16ch;">
      <template #body="{ data }">
        <span>{{ data.work_done_date ?? '—' }}</span>
      </template>
    </Column>
    <Column field="worker_name" header="Монтажник" style="inline-size: 18ch;">
      <template #body="{ data }">
        <span>{{ data.worker_name ?? '—' }}</span>
      </template>
    </Column>
    <Column field="status_label" header="Статус" style="inline-size: 14ch;">
      <template #body="{ data }">
        <Tag :value="data.status_label" :severity="statusSeverity(data.status)" />
      </template>
    </Column>
    <Column header="" style="inline-size: 8ch;">
      <template #body="{ data }">
        <Button
          v-if="data.contract_id"
          icon="pi pi-external-link"
          text
          rounded
          aria-label="Открыть договор"
          @click="router.push(`/operations/contracts/${data.contract_id}`)"
        />
      </template>
    </Column>
    <Column header="" style="inline-size: 16ch;">
      <template #body="{ data }">
        <Button
          v-if="data.can_edit"
          label="Назначить дату"
          size="small"
          outlined
          @click="emit('assign', data)"
        />
      </template>
    </Column>
    <template #empty>
      <div class="text-center py-6 text-muted">
        {{ hasRows ? 'Нет данных.' : 'Монтажей нет.' }}
      </div>
    </template>
  </DataTable>
</template>
