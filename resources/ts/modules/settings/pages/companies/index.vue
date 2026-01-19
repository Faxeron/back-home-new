<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import CompaniesTable from '@/modules/settings/components/CompaniesTable.vue'
import { useTableInfinite } from '@/composables/useTableLazy'
import { useDictionaryFilters, type DictionaryFilterDef } from '@/composables/useDictionaryFilters'
import { COMPANY_TABLE } from '@/modules/settings/config/companiesTable.config'
import type { Company } from '@/types/finance'

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
} = useTableInfinite<Company>({
  endpoint: 'settings/companies',
  perPage: COMPANY_TABLE.perPage,
  rowHeight: COMPANY_TABLE.rowHeight,
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
  <CompaniesTable
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
