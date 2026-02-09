<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useAbility } from '@casl/vue'
import CashflowItemsTable from '@/modules/finance/components/settings/CashflowItemsTable.vue'
import { useTableInfinite } from '@/composables/useTableLazy'
import { useDictionaryFilters, type DictionaryFilterDef } from '@/composables/useDictionaryFilters'
import { CASHFLOW_ITEM_TABLE } from '@/modules/finance/config/cashflowItemsTable.config'
import { useDictionariesStore } from '@/stores/dictionaries'
import type { CashflowItem } from '@/types/finance'

const tableRef = ref<any>(null)
const scrollHeight = ref('700px')
const reloadRef = ref<() => void>(() => {})
const ability = useAbility()
const canCreate = computed(() => ability.can('create', 'settings.cashflow_items'))
const canEdit = computed(() => ability.can('edit', 'settings.cashflow_items'))
const canDelete = computed(() => ability.can('delete', 'settings.cashflow_items'))

const dictionaries = useDictionariesStore()

const filterDefs: DictionaryFilterDef[] = [
  { key: 'code', kind: 'text', queryKey: 'code', debounce: true },
  { key: 'name', kind: 'text', queryKey: 'name', debounce: true },
  { key: 'section', kind: 'select', queryKey: 'section' },
  { key: 'direction', kind: 'select', queryKey: 'direction' },
  { key: 'parent_id', kind: 'select', queryKey: 'parent_id' },
  { key: 'is_active', kind: 'select', queryKey: 'is_active' },
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
} = useTableInfinite<CashflowItem>({
  endpoint: 'cashflow-items',
  perPage: CASHFLOW_ITEM_TABLE.perPage,
  rowHeight: CASHFLOW_ITEM_TABLE.rowHeight,
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
  await dictionaries.loadCashflowItems(true)
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
  <CashflowItemsTable
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
