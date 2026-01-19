<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import TransactionsTable from '@/modules/finance/components/TransactionsTable.vue'
import { useTableInfinite } from '@/composables/useTableLazy'
import { useTransactionFilters } from '@/modules/finance/composables/useTransactionFilters'
import { TRANSACTION_TABLE } from '@/modules/finance/config/transactionsTable.config'
import { useDictionariesStore } from '@/stores/dictionaries'
import type { Transaction } from '@/types/finance'

const dictionaries = useDictionariesStore()
const tableRef = ref<any>(null)
const scrollHeight = ref('700px')
const reloadRef = ref<() => void>(() => {})

const { filters, serverParams, resetFilters, handleSort } = useTransactionFilters({
  onChange: () => reloadRef.value(),
})

const {
  data,
  total: totalRecords,
  loading,
  reset: resetData,
  virtualScrollerOptions,
} = useTableInfinite<Transaction>({
  endpoint: 'finance/transactions',
  include: TRANSACTION_TABLE.include,
  perPage: TRANSACTION_TABLE.perPage,
  rowHeight: TRANSACTION_TABLE.rowHeight,
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
    dictionaries.loadTransactionTypes(),
    dictionaries.loadPaymentMethods(),
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
  <TransactionsTable
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
