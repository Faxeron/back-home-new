<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import TransactionTypesTable from '@/components/tables/settings/TransactionTypesTable.vue'
import { useTableInfinite } from '@/composables/useTableLazy'
import { useDictionaryFilters, type DictionaryFilterDef } from '@/composables/useDictionaryFilters'
import { TRANSACTION_TYPE_TABLE } from '@/config/tables/transaction-types'
import type { TransactionType } from '@/types/finance'

const tableRef = ref<any>(null)
const scrollHeight = ref('700px')
const reloadRef = ref<() => void>(() => {})

const filterDefs: DictionaryFilterDef[] = [
  { key: 'name', kind: 'text', queryKey: 'q', debounce: true },
]

const { filters, serverParams, resetFilters, handleSort } = useDictionaryFilters(filterDefs, {
  onChange: () => reloadRef.value(),
})

const {
  data,
  total: totalRecords,
  loading,
  reset: resetData,
  virtualScrollerOptions,
} = useTableInfinite<TransactionType>({
  endpoint: 'finance/transaction-types',
  perPage: TRANSACTION_TYPE_TABLE.perPage,
  rowHeight: TRANSACTION_TYPE_TABLE.rowHeight,
  params: () => serverParams.value,
})

reloadRef.value = () => {
  resetData()
}

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

onMounted(async () => {
  await resetData()
  await nextTick()
  updateScrollHeight()
  window.addEventListener('resize', handleResize)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
})
</script>

<template>
  <TransactionTypesTable
    ref="tableRef"
    v-model:filters="filters"
    :rows="data"
    :loading="loading"
    :totalRecords="totalRecords"
    :scrollHeight="scrollHeight"
    :virtualScrollerOptions="virtualScrollerOptions"
    @sort="handleSort"
    @reset-filters="resetFilters"
  />
</template>
