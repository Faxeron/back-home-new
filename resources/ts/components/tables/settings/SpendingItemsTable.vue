<script setup lang="ts">
import { computed } from 'vue'
import DictionaryTable, { type DictionaryColumn } from './DictionaryTable.vue'
import { SPENDING_ITEM_COLUMNS } from '@/config/tables/spending-items'
import { useDictionariesStore } from '@/stores/dictionaries'
import type { SpendingItem } from '@/types/finance'

type SpendingItemRow = SpendingItem & {
  fund_name?: string
}

const props = defineProps<{
  rows: SpendingItem[]
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

const dictionaries = useDictionariesStore()

const fundMap = computed(
  () => new Map(dictionaries.spendingFunds.map(item => [String(item.id), item.name])),
)

const rows = computed<SpendingItemRow[]>(() =>
  (props.rows || []).map(row => ({
    ...row,
    fund_name: row.fond_id != null ? fundMap.value.get(String(row.fond_id)) ?? '' : '',
  })),
)

const formatBool = (value?: boolean | null) => (value === true ? 'ДА' : value === false ? 'НЕТ' : '')

const columns = computed<DictionaryColumn[]>(() => [
  { ...SPENDING_ITEM_COLUMNS.id },
  { ...SPENDING_ITEM_COLUMNS.name },
  {
    ...SPENDING_ITEM_COLUMNS.fund,
    options: dictionaries.spendingFunds,
    optionLabel: 'name',
    optionValue: 'id',
    body: row => row.fund_name ?? '',
  },
  { ...SPENDING_ITEM_COLUMNS.description },
  { ...SPENDING_ITEM_COLUMNS.isActive, body: row => formatBool(row.is_active) },
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
