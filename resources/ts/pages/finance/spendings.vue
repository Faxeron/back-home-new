<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import SpendingsTable from '@/components/tables/spendings/SpendingsTable.vue'
import { useTableInfinite } from '@/composables/useTableLazy'
import { useSpendingFilters } from '@/composables/useSpendingFilters'
import { SPENDING_TABLE } from '@/config/tables/spendings'
import { useDictionariesStore } from '@/stores/dictionaries'
import type { Spending } from '@/types/finance'

const dictionaries = useDictionariesStore()
const tableRef = ref<any>(null)
const scrollHeight = ref('700px')
const reloadRef = ref<() => void>(() => {})

const { filters, serverParams, resetFilters, handleSort } = useSpendingFilters({
  onChange: () => reloadRef.value(),
})

const {
  data,
  total: totalRecords,
  loading,
  reset: resetData,
  virtualScrollerOptions,
} = useTableInfinite<Spending>({
  endpoint: 'finance/spendings',
  include: SPENDING_TABLE.include,
  perPage: SPENDING_TABLE.perPage,
  rowHeight: SPENDING_TABLE.rowHeight,
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
  await Promise.all([
    dictionaries.loadCashBoxes(true),
    dictionaries.loadSpendingFunds(),
    dictionaries.loadSpendingItems(),
    dictionaries.loadCounterparties(),
  ])
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
  <SpendingsTable
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
