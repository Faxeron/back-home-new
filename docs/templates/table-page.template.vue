<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import __Entity__Table from '@/components/tables/__entity__/__Entity__Table.vue'
import { useTableInfinite } from '@/composables/useTableLazy'
import { use__Entity__Filters } from '@/composables/use__Entity__Filters'
import { __ENTITY___TABLE } from '@/config/tables/__entity__'
import { useDictionariesStore } from '@/stores/dictionaries'
import type { __Entity__ } from '@/types/__domain__'

const dictionaries = useDictionariesStore()
const tableRef = ref<any>(null)
const scrollHeight = ref('700px')
const reloadRef = ref<() => void>(() => {})

const { filters, serverParams, resetFilters, handleSort } = use__Entity__Filters({
  onChange: () => reloadRef.value(),
})

const {
  data,
  total: totalRecords,
  loading,
  reset: resetData,
  virtualScrollerOptions,
} = useTableInfinite<__Entity__>({
  endpoint: '__domain__/__entity__',
  include: __ENTITY___TABLE.include,
  perPage: __ENTITY___TABLE.perPage,
  rowHeight: __ENTITY___TABLE.rowHeight,
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
  // TODO: load dictionaries only if needed
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
  <__Entity__Table
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
