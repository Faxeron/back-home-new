<script setup lang="ts">
import { computed } from 'vue'
import DictionaryTable, { type DictionaryColumn } from '@/components/tables/settings/DictionaryTable.vue'
import { DISTRICT_COLUMNS } from '@/modules/settings/config/districtsTable.config'
import { useDictionariesStore } from '@/stores/dictionaries'
import type { District } from '@/types/finance'

type DistrictRow = District & {
  city_name?: string
}

const props = defineProps<{
  rows: District[]
  loading: boolean
  totalRecords: number
  scrollHeight: string
  virtualScrollerOptions: Record<string, any>
  filters: any
}>()

const emit = defineEmits<{
  (e: 'update:filters', value: any): void
  (e: 'sort', event: any): void
  (e: 'reset-filters'): void
}>()

const dictionaries = useDictionariesStore()

const cityMap = computed(
  () => new Map(dictionaries.cities.map(item => [String(item.id), item.name])),
)

const rows = computed<DistrictRow[]>(() =>
  (props.rows || []).map(row => ({
    ...row,
    city_name: row.city_id != null ? cityMap.value.get(String(row.city_id)) ?? '' : '',
  })),
)

const formatBool = (value?: boolean | null) => (value === true ? 'ДА' : value === false ? 'НЕТ' : '')

const columns = computed<DictionaryColumn[]>(() => [
  { ...DISTRICT_COLUMNS.id },
  { ...DISTRICT_COLUMNS.name },
  {
    ...DISTRICT_COLUMNS.city,
    options: dictionaries.cities,
    optionLabel: 'name',
    optionValue: 'id',
    body: row => row.city_name ?? '',
  },
  { ...DISTRICT_COLUMNS.isActive, body: row => formatBool(row.is_active) },
])
</script>

<template>
  <DictionaryTable
    :filters="filters"
    :rows="rows"
    :loading="loading"
    :totalRecords="totalRecords"
    :scrollHeight="scrollHeight"
    :virtualScrollerOptions="virtualScrollerOptions"
    :columns="columns"
    @update:filters="emit('update:filters', $event)"
    @sort="emit('sort', $event)"
    @reset-filters="emit('reset-filters')"
  />
</template>
