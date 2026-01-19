<script setup lang="ts">
import { computed } from 'vue'
import DictionaryTable, { type DictionaryColumn } from '@/components/tables/settings/DictionaryTable.vue'
import { CASH_BOX_COLUMNS } from '@/modules/finance/config/cashBoxesTable.config'
import type { CashBox } from '@/types/finance'

type CashBoxRow = CashBox & {
  company_name?: string
}

const props = defineProps<{
  rows: CashBox[]
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

const rows = computed<CashBoxRow[]>(() =>
  (props.rows || []).map(row => ({
    ...row,
    company_name: row.company?.name ?? '',
  })),
)

const formatBool = (value?: boolean | null) => (value === true ? 'ДА' : value === false ? 'НЕТ' : '')

const columns = computed<DictionaryColumn[]>(() => [
  { ...CASH_BOX_COLUMNS.id },
  { ...CASH_BOX_COLUMNS.name },
  { ...CASH_BOX_COLUMNS.company, body: row => row.company_name ?? '' },
  { ...CASH_BOX_COLUMNS.description },
  { ...CASH_BOX_COLUMNS.isActive, body: row => formatBool(row.is_active) },
])
</script>

<template>
  <DictionaryTable
    :filters="filters"
    :rows="rows"
    :loading="loading"
    :totalRecords="totalRecords"
    :scrollHeight="scrollHeight"
    :virtualScrollerOptions="virtualScrollerOptions"
    :columns="columns"
    @update:filters="emit('update:filters', $event)"
    @sort="emit('sort', $event)"
    @reset-filters="emit('reset-filters')"
  />
</template>
