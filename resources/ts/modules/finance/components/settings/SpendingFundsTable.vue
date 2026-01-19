<script setup lang="ts">
import { computed } from 'vue'
import DictionaryTable, { type DictionaryColumn } from '@/components/tables/settings/DictionaryTable.vue'
import { SPENDING_FUND_COLUMNS } from '@/modules/finance/config/spendingFundsTable.config'
import type { SpendingFund } from '@/types/finance'

const props = defineProps<{
  rows: SpendingFund[]
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

const formatBool = (value?: boolean | null) => (value === true ? 'ДА' : value === false ? 'НЕТ' : '')

const columns = computed<DictionaryColumn[]>(() => [
  { ...SPENDING_FUND_COLUMNS.id },
  { ...SPENDING_FUND_COLUMNS.name },
  { ...SPENDING_FUND_COLUMNS.description },
  { ...SPENDING_FUND_COLUMNS.itemsCount },
  { ...SPENDING_FUND_COLUMNS.isActive, body: row => formatBool(row.is_active) },
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
