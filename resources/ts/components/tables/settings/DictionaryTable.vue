<script setup lang="ts">
import { computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Button from 'primevue/button'

export type DictionaryColumn = {
  field: string
  header: string
  sortable?: boolean
  width?: string
  filter?: 'text' | 'select' | 'number'
  options?: any[]
  optionLabel?: string
  optionValue?: string
  body?: (row: any) => unknown
}

const props = defineProps<{
  rows: unknown[]
  loading: boolean
  totalRecords: number
  scrollHeight: string
  virtualScrollerOptions: Record<string, any>
  filters: any
  columns: DictionaryColumn[]
}>()

const emit = defineEmits<{
  (e: 'update:filters', value: any): void
  (e: 'sort', event: any): void
  (e: 'reset-filters'): void
}>()

const filtersModel = computed({
  get: () => props.filters,
  set: value => emit('update:filters', value),
})

const totalLabel = computed(() => Number(props.totalRecords ?? 0).toLocaleString('ru-RU'))
</script>

<template>
  <DataTable
    v-model:filters="filtersModel"
    :value="rows"
    filterDisplay="row"
    dataKey="id"
    class="p-datatable-sm"
    :loading="loading"
    :totalRecords="totalRecords"
    scrollable
    :scrollHeight="scrollHeight"
    :virtualScrollerOptions="virtualScrollerOptions"
    lazy
    stripedRows
    @sort="emit('sort', $event)"
  >
    <template #header>
      <div class="flex items-center justify-between gap-4">
        <TableTotalLabel label="Всего" :value="totalLabel" />
        <Button
          label="Сброс фильтров"
          text
          @click="emit('reset-filters')"
        />
      </div>
    </template>

    <Column
      v-for="col in columns"
      :key="col.field"
      :field="col.field"
      :header="col.header"
      :sortable="col.sortable"
      :showFilterMenu="false"
      :style="col.width ? `inline-size: ${col.width};` : undefined"
      :headerStyle="col.width ? `inline-size: ${col.width};` : undefined"
      :bodyStyle="col.width ? `inline-size: ${col.width};` : undefined"
    >
      <template
        v-if="col.filter === 'text'"
        #filter="{ filterModel, filterCallback }"
      >
        <InputText
          v-model="filterModel.value"
          class="w-full"
          @input="filterCallback()"
        />
      </template>

      <template
        v-else-if="col.filter === 'number'"
        #filter="{ filterModel, filterCallback }"
      >
        <InputNumber
          v-model="filterModel.value"
          class="w-full"
          @update:modelValue="filterCallback()"
        />
      </template>

      <template
        v-else-if="col.filter === 'select'"
        #filter="{ filterModel, filterCallback }"
      >
        <Select
          v-model="filterModel.value"
          :options="col.options ?? []"
          :optionLabel="col.optionLabel ?? 'name'"
          :optionValue="col.optionValue ?? 'id'"
          class="w-full"
          @change="filterCallback()"
        />
      </template>

      <template #body="{ data }">
        {{ typeof col.body === 'function' ? col.body(data) : data?.[col.field] ?? '' }}
      </template>
    </Column>

    <template #empty>
      <div class="text-center py-6 text-muted">Нет данных.</div>
    </template>

    <template #loading>
      <div class="text-center py-6 text-muted">Загрузка...</div>
    </template>
  </DataTable>
</template>
