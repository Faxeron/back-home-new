<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import DistrictsTable from '@/modules/settings/components/DistrictsTable.vue'
import { useTableInfinite } from '@/composables/useTableLazy'
import { useDictionaryFilters, type DictionaryFilterDef } from '@/composables/useDictionaryFilters'
import { DISTRICT_TABLE } from '@/modules/settings/config/districtsTable.config'
import { useDictionariesStore } from '@/stores/dictionaries'
import type { District } from '@/types/finance'

const dictionaries = useDictionariesStore()
const tableRef = ref<any>(null)
const scrollHeight = ref('700px')
const reloadRef = ref<() => void>(() => {})
const isInitializing = ref(true)

const filterDefs: DictionaryFilterDef[] = [
  { key: 'name', kind: 'text', queryKey: 'q', debounce: true },
  { key: 'city_id', kind: 'select', queryKey: 'city_id' },
]

const { filters, serverParams, resetFilters, handleSort } = useDictionaryFilters(filterDefs, {
  onChange: () => {
    if (!isInitializing.value) reloadRef.value()
  },
})

const {
  data,
  total: totalRecords,
  loading,
  reset: resetData,
  virtualScrollerOptions,
} = useTableInfinite<District>({
  endpoint: 'settings/cities-districts',
  perPage: DISTRICT_TABLE.perPage,
  rowHeight: DISTRICT_TABLE.rowHeight,
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
  await dictionaries.loadCities()
  if (!filters.value.city_id?.value && dictionaries.cities.length) {
    filters.value.city_id.value = dictionaries.cities[0].id
  }
  await resetData()
  await nextTick()
  updateScrollHeight()
  window.addEventListener('resize', handleResize)
  isInitializing.value = false
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
})
</script>

<template>
  <DistrictsTable
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
