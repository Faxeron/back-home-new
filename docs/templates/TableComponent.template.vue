<script setup lang="ts">
import { computed, ref } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Calendar from 'primevue/calendar'
import Button from 'primevue/button'
import Popover from 'primevue/popover'
import { useDictionariesStore } from '@/stores/dictionaries'
import { __ENTITY___COLUMNS, __ENTITY___BOOLEAN_OPTIONS } from '@/config/tables/__entity__'
import type { __Entity__ } from '@/types/__domain__'

type __Entity__Row = __Entity__ & {
  // optional computed fields
}

const props = defineProps<{
  rows: __Entity__[]
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

const filtersModel = computed({
  get: () => props.filters,
  set: value => emit('update:filters', value),
})

const totalLabel = computed(() => Number(props.totalRecords ?? 0).toLocaleString('en-US'))
const dictionaries = useDictionariesStore()

const somePanel = ref<any>(null)

const rows = computed<__Entity__Row[]>(() =>
  (props.rows || []).map(row => ({
    ...row,
  })),
)

const togglePanel = (panel: { toggle: (event: Event) => void } | null, event: Event) => {
  panel?.toggle(event)
}
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
        <div class="text-sm text-muted">Total: {{ totalLabel }}</div>
        <Button
          label="Reset filters"
          text
          @click="emit('reset-filters')"
        />
      </div>
    </template>

    <Column
      :field="__ENTITY___COLUMNS.id.field"
      :header="__ENTITY___COLUMNS.id.header"
      :sortable="__ENTITY___COLUMNS.id.sortable"
    >
      <template #filter="{ filterModel, filterCallback }">
        <InputText
          v-model="filterModel.value"
          class="w-full"
          @input="filterCallback()"
        />
      </template>
      <template #body="{ data }">
        {{ data.id }}
      </template>
    </Column>

    <Column
      :field="__ENTITY___COLUMNS.status.field"
      :header="__ENTITY___COLUMNS.status.header"
    >
      <template #filter="{ filterModel, filterCallback }">
        <div class="flex items-center gap-2">
          <Select
            v-model="filterModel.value"
            :options="__ENTITY___BOOLEAN_OPTIONS"
            optionLabel="label"
            optionValue="value"
            class="w-full"
            @change="filterCallback()"
          />
          <Button
            icon="pi pi-calendar"
            text
            @click="togglePanel(somePanel, $event)"
          />
          <Popover ref="somePanel">
            <Calendar
              v-model="filtersModel.status_date.value.from"
              placeholder="From"
              dateFormat="yy-mm-dd"
              @update:modelValue="filterCallback()"
            />
          </Popover>
        </div>
      </template>
      <template #body="{ data }">
        {{ data.status }}
      </template>
    </Column>

    <template #empty>
      <div class="text-center py-6 text-muted">No data</div>
    </template>

    <template #loading>
      <div class="text-center py-6 text-muted">Loading...</div>
    </template>
  </DataTable>
</template>
