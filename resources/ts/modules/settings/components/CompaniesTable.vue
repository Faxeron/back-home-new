<script setup lang="ts">
import { computed } from 'vue'
import DictionaryTable, { type DictionaryColumn } from '@/components/tables/settings/DictionaryTable.vue'
import { COMPANY_COLUMNS } from '@/modules/settings/config/companiesTable.config'
import type { Company } from '@/types/finance'

const props = defineProps<{
  rows: Company[]
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

const columns = computed<DictionaryColumn[]>(() => [
  { ...COMPANY_COLUMNS.id },
  { ...COMPANY_COLUMNS.name },
  { ...COMPANY_COLUMNS.code },
  { ...COMPANY_COLUMNS.phone },
  { ...COMPANY_COLUMNS.email },
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
