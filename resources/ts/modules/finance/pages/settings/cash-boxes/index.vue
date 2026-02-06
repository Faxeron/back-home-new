<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useAbility } from '@casl/vue'
import CashBoxesTable from '@/modules/finance/components/settings/CashBoxesTable.vue'
import { useTableInfinite } from '@/composables/useTableLazy'
import { useDictionaryFilters, type DictionaryFilterDef } from '@/composables/useDictionaryFilters'
import { CASH_BOX_TABLE } from '@/modules/finance/config/cashBoxesTable.config'
import type { CashBox } from '@/types/finance'

const tableRef = ref<any>(null)
const scrollHeight = ref('700px')
const reloadRef = ref<() => void>(() => {})
const ability = useAbility()
const canCreate = computed(() => ability.can('create', 'settings.cash_boxes'))
const canEdit = computed(() => ability.can('edit', 'settings.cash_boxes'))
const canDelete = computed(() => ability.can('delete', 'settings.cash_boxes'))

const filterDefs: DictionaryFilterDef[] = [
  { key: 'id', kind: 'number', queryKey: 'id' },
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
} = useTableInfinite<CashBox>({
  endpoint: 'settings/cash-boxes',
  perPage: CASH_BOX_TABLE.perPage,
  rowHeight: CASH_BOX_TABLE.rowHeight,
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
  <CashBoxesTable
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
