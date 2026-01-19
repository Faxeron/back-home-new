<script setup lang="ts">

import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

import { useRouter } from 'vue-router'

import DataTable from 'primevue/datatable'

import Column from 'primevue/column'

import InputText from 'primevue/inputtext'

import Button from 'primevue/button'

import { useTableInfinite } from '@/composables/useTableLazy'

import { $api } from '@/utils/api'

import type { EstimateTemplateMaterial } from '@/modules/estimates/types/estimate-templates.types'
import {
  TEMPLATE_MATERIALS_HEADERS,
  TEMPLATE_MATERIALS_LABELS,
  formatTemplateDate,
} from '@/modules/estimates/config/estimateTemplates.config'



const router = useRouter()

const tableRef = ref<any>(null)

const scrollHeight = ref('700px')

const search = ref('')



const params = computed(() => ({

  q: search.value || undefined,

}))



const {

  data,

  total: totalRecords,

  loading,

  reset,

  virtualScrollerOptions,

} = useTableInfinite<EstimateTemplateMaterial>({

  endpoint: 'estimate-templates/materials',

  params: () => params.value,

  perPage: 50,

  rowHeight: 52,

})



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



const handleCreate = () => {

  router.push({ path: '/estimate-templates/materials/new' })

}



const handleEdit = (row: EstimateTemplateMaterial) => {

  router.push({ path: `/estimate-templates/materials/${row.id}` })

}



const handleDelete = async (row: EstimateTemplateMaterial) => {

  if (!row?.id) return

  if (!window.confirm(TEMPLATE_MATERIALS_LABELS.confirmDelete)) return

  await $api(`/estimate-templates/materials/${row.id}`, { method: 'DELETE' })

  await reset()

}



const totalLabel = computed(() => Number(totalRecords.value ?? 0).toLocaleString('ru-RU'))



const formatDate = formatTemplateDate



onMounted(async () => {

  await reset()

  await nextTick()

  updateScrollHeight()

  window.addEventListener('resize', handleResize)

})



watch(search, () => {

  reset()

})



onBeforeUnmount(() => {

  window.removeEventListener('resize', handleResize)

})

</script>



<template>

  <DataTable

    ref="tableRef"

    :value="data"

    dataKey="id"

    class="p-datatable-sm"

    :loading="loading"

    :totalRecords="totalRecords"

    scrollable

    :scrollHeight="scrollHeight"

    :virtualScrollerOptions="virtualScrollerOptions"

    lazy

    stripedRows

  >

    <template #header>

      <div class="flex flex-wrap items-center justify-between gap-3">

        <div class="flex items-center gap-2">

          <InputText

            v-model="search"

            class="w-64"

            :placeholder="TEMPLATE_MATERIALS_LABELS.searchPlaceholder"

          />

        </div>

        <div class="flex items-center gap-2">

          <TableTotalLabel :label="TEMPLATE_MATERIALS_LABELS.total" :value="totalLabel" />

          <Button

            :label="TEMPLATE_MATERIALS_LABELS.create"

            icon="pi pi-plus"

            size="small"

            @click="handleCreate"

          />

        </div>

      </div>

    </template>



    <Column field="id" :header="TEMPLATE_MATERIALS_HEADERS.id" style="inline-size: 6ch;" />

    <Column field="title" :header="TEMPLATE_MATERIALS_HEADERS.title" />

    <Column

      field="items_count"

      :header="TEMPLATE_MATERIALS_HEADERS.itemsCount"

      style="inline-size: 10ch;"

    >

      <template #body="{ data: row }">

        {{ Array.isArray(row.items) ? row.items.length : 0 }}

      </template>

    </Column>

    <Column field="updated_at" :header="TEMPLATE_MATERIALS_HEADERS.updatedAt" style="inline-size: 12ch;">

      <template #body="{ data: row }">

        {{ formatDate(row.updated_at) }}

      </template>

    </Column>

    <Column header="" style="inline-size: 10ch;">

      <template #body="{ data: row }">

        <div class="flex items-center gap-1">

          <Button

            icon="pi pi-pencil"

            text

            :aria-label="TEMPLATE_MATERIALS_LABELS.editAria"

            @click="handleEdit(row)"

          />

          <Button

            icon="pi pi-trash"

            text

            severity="danger"

            :aria-label="TEMPLATE_MATERIALS_LABELS.deleteAria"

            @click="handleDelete(row)"

          />

        </div>

      </template>

    </Column>



    <template #empty>

      <div class="text-center py-6 text-muted">{{ TEMPLATE_MATERIALS_LABELS.empty }}</div>

    </template>



    <template #loading>

      <div class="text-center py-6 text-muted">{{ TEMPLATE_MATERIALS_LABELS.loading }}</div>

    </template>

  </DataTable>

</template>
