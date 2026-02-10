<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useAbility } from '@casl/vue'
import SpendingItemsTable from '@/modules/finance/components/settings/SpendingItemsTable.vue'
import { useTableInfinite } from '@/composables/useTableLazy'
import { useDictionaryFilters, type DictionaryFilterDef } from '@/composables/useDictionaryFilters'
import { SPENDING_ITEM_TABLE } from '@/modules/finance/config/spendingItemsTable.config'
import { useDictionariesStore } from '@/stores/dictionaries'
import type { SpendingItem } from '@/types/finance'

const dictionaries = useDictionariesStore()
const tableRef = ref<any>(null)
const scrollHeight = ref('700px')
const reloadRef = ref<() => void>(() => {})
const ability = useAbility()
const canCreate = computed(() => ability.can('create', 'settings.spending_items'))
const canEdit = computed(() => ability.can('edit', 'settings.spending_items'))
const canDelete = computed(() => ability.can('delete', 'settings.spending_items'))

const filterDefs: DictionaryFilterDef[] = [
  { key: 'name', kind: 'text', queryKey: 'q', debounce: true },
  { key: 'fond_id', kind: 'select', queryKey: 'fund_id' },
  { key: 'cashflow_item_id', kind: 'select', queryKey: 'cashflow_item_id' },
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
} = useTableInfinite<SpendingItem>({
  endpoint: 'settings/spending-items',
  perPage: SPENDING_ITEM_TABLE.perPage,
  rowHeight: SPENDING_ITEM_TABLE.rowHeight,
  params: () => serverParams.value,
})

reloadRef.value = () => {
  resetData()
}

const updateScrollHeight = () => {
  const rawEl = tableRef.value?.$el
  const tableEl = Array.isArray(rawEl) ? rawEl[0] : rawEl
  if (!tableEl || typeof (tableEl as HTMLElement).getBoundingClientRect !== 'function') return
  const rect = (tableEl as HTMLElement).getBoundingClientRect()
  const padding = 24
  const nextHeight = Math.max(320, window.innerHeight - rect.top - padding)
  scrollHeight.value = `${Math.floor(nextHeight)}px`
}

const handleResize = () => {
  updateScrollHeight()
}

onMounted(async () => {
  await dictionaries.loadSpendingFunds()
  await dictionaries.loadCashflowItems()
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
  <SpendingItemsTable
    ref="tableRef"
    v-model:filters="filters"
    :rows="data"
    :loading="loading"
    :totalRecords="totalRecords"
    :scrollHeight="scrollHeight"
    :virtualScrollerOptions="virtualScrollerOptions"
    :canCreate="canCreate"
    :canEdit="canEdit"
    :canDelete="canDelete"
    @sort="handleSort"
    @reset-filters="resetFilters"
    @reload="resetData"
  />
</template>
