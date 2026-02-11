<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import ReceiptsTable from '@/modules/finance/components/ReceiptsTable.vue'
import { useTableInfinite } from '@/composables/useTableLazy'
import { useReceiptFilters } from '@/modules/finance/composables/useReceiptFilters'
import { RECEIPT_TABLE } from '@/modules/finance/config/receiptsTable.config'
import { useDictionariesStore } from '@/stores/dictionaries'
import type { Receipt } from '@/types/finance'

const dictionaries = useDictionariesStore()
const tableRef = ref<any>(null)
const scrollHeight = ref('700px')
const reloadRef = ref<() => void>(() => {})

const { filters, serverParams, resetFilters, handleSort } = useReceiptFilters({
  onChange: () => reloadRef.value(),
})

const {
  data,
  total: totalRecords,
  loading,
  reset: resetData,
  virtualScrollerOptions,
} = useTableInfinite<Receipt>({
  endpoint: 'finance/receipts',
  include: RECEIPT_TABLE.include,
  perPage: RECEIPT_TABLE.perPage,
  rowHeight: RECEIPT_TABLE.rowHeight,
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
    dictionaries.loadCounterparties(),
    dictionaries.loadCashflowItems(),
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
  <ReceiptsTable
    ref="tableRef"
    v-model:filters="filters"
    :rows="data"
    :loading="loading"
    :totalRecords="totalRecords"
    :scrollHeight="scrollHeight"
    :virtualScrollerOptions="virtualScrollerOptions"
    @sort="handleSort"
    @reset-filters="resetFilters"
    @reload="resetData"
  />
</template>
