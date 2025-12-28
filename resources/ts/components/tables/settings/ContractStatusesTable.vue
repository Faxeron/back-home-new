<script setup lang="ts">
import { computed } from 'vue'
import DictionaryTable, { type DictionaryColumn } from './DictionaryTable.vue'
import { CONTRACT_STATUS_COLUMNS } from '@/config/tables/contract-statuses'
import type { ContractStatus } from '@/types/finance'

const props = defineProps<{
  rows: ContractStatus[]
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
  { ...CONTRACT_STATUS_COLUMNS.id },
  { ...CONTRACT_STATUS_COLUMNS.name },
  { ...CONTRACT_STATUS_COLUMNS.code },
  { ...CONTRACT_STATUS_COLUMNS.color },
  { ...CONTRACT_STATUS_COLUMNS.sortOrder },
  { ...CONTRACT_STATUS_COLUMNS.isActive, body: row => formatBool(row.is_active) },
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
