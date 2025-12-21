<script setup lang="ts">
import { computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import ColumnBuilder from './table/ColumnBuilder.vue'

type FilterType = 'text' | 'number' | 'boolean' | 'select' | 'dateRange'

interface ColumnMeta {
  field: string
  header: string
  [key: string]: unknown
}

interface ConfigColumn {
  field: string
  label: string
  subLabel?: string
  sortable?: boolean
  filter?: FilterType
  options?: any[]
  body?: (row: any) => unknown
}

interface Props {
  // Config mode (paginator + filters + simple columns)
  config?: {
    columns: ConfigColumn[]
    rowsPerPage?: number
    paginator?: boolean
  }
  data?: unknown[]
  total?: number
  load?: (event: any) => void

  // Legacy mode (ColumnBuilder + virtual scroll)
  rows?: unknown[]
  totalRecords?: number
  loading?: boolean
  columns?: ColumnMeta[]
  rowHeight?: number
  scrollHeight?: string
  dataKey?: string
}

const props = withDefaults(defineProps<Props>(), {
  rowHeight: 50,
  scrollHeight: '700px',
  dataKey: 'id',
})

const emit = defineEmits<{
  (e: 'lazy-load', event: any): void
  (e: 'row-click', event: any): void
}>()

const virtualScrollerOptions = computed(() => ({
  lazy: true,
  itemSize: props.rowHeight,
  showLoader: true,
  onLazyLoad: (event: any) => emit('lazy-load', event),
}))

const handleLazyLoad = (event: any) => {
  emit('lazy-load', event)
  props.load?.(event)
}

const genFilter = (col: ConfigColumn) => {
  switch (col.filter) {
    case 'text':
      return InputText
    case 'number':
      return InputNumber
    case 'boolean':
    case 'select':
      return Dropdown
    case 'dateRange':
      return Calendar
    default:
      return null
  }
}
</script>

<template>
  <!-- Config mode (paginator + filters in header row) -->
  <DataTable
    v-if="config"
    :value="data || rows || []"
    :lazy="true"
    :loading="loading"
    :paginator="config.paginator ?? true"
    :rows="config.rowsPerPage ?? 25"
    :totalRecords="total ?? totalRecords ?? 0"
    filterDisplay="row"
    sortMode="single"
    class="p-datatable-sm"
    @lazy-load="handleLazyLoad"
    @row-click="emit('row-click', $event)"
  >
    <Column
      v-for="col in config.columns"
      :key="col.field"
      :field="col.field"
      :header="col.label"
      :sortable="col.sortable"
      :filter="!!col.filter"
      :showFilterMenu="false"
    >
      <!-- FILTER -->
      <template v-if="genFilter(col)" #filter>
        <component :is="genFilter(col)" :options="col.options" selectionMode="range" />
      </template>

      <!-- BODY -->
      <template #body="{ data }">
        <div class="whitespace-pre-line leading-tight py-1">
          {{ typeof col.body === 'function' ? col.body(data) : data[col.field] }}
        </div>
      </template>
    </Column>

    <template #empty>
      <div class="text-center py-6 text-muted">Нет данных</div>
    </template>

    <template #loading>
      <div class="text-center py-6 text-muted">Загрузка...</div>
    </template>
  </DataTable>

  <!-- ColumnBuilder mode (virtual scroll) -->
  <DataTable
    v-else
    :value="rows || []"
    :loading="loading"
    :totalRecords="totalRecords || 0"
    scrollable
    :scrollHeight="scrollHeight"
    :dataKey="dataKey"
    :virtualScrollerOptions="virtualScrollerOptions"
    lazy
    @row-click="emit('row-click', $event)"
  >
    <ColumnBuilder :columns="columns || []" />

    <template #empty>
      <div class="text-center py-6 text-muted">Нет данных</div>
    </template>

    <template #loading>
      <div class="text-center py-6 text-muted">Загрузка...</div>
    </template>
  </DataTable>
</template>

<style scoped>
.whitespace-pre-line {
  white-space: pre-line;
}
</style>
