<script setup lang="ts">
import { computed } from 'vue'
import DictionaryTable, { type DictionaryColumn } from '@/components/tables/settings/DictionaryTable.vue'
import { TRANSACTION_TYPE_COLUMNS } from '@/modules/finance/config/transactionTypesTable.config'
import type { TransactionType } from '@/types/finance'

const props = defineProps<{
  rows: TransactionType[]
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
  { ...TRANSACTION_TYPE_COLUMNS.id },
  { ...TRANSACTION_TYPE_COLUMNS.code },
  { ...TRANSACTION_TYPE_COLUMNS.name },
  { ...TRANSACTION_TYPE_COLUMNS.sign },
  { ...TRANSACTION_TYPE_COLUMNS.isActive, body: row => formatBool(row.is_active) },
  { ...TRANSACTION_TYPE_COLUMNS.sortOrder },
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
