<script setup lang="ts">

import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

import { useRouter } from 'vue-router'
import { useAbility } from '@casl/vue'

import DataTable from 'primevue/datatable'

import Column from 'primevue/column'

import InputText from 'primevue/inputtext'

import Button from 'primevue/button'

import { useTableInfinite } from '@/composables/useTableLazy'

import { $api } from '@/utils/api'

import type { EstimateTemplateSeptik } from '@/modules/estimates/types/estimate-templates.types'
import {
  TEMPLATE_EMPTY_TEXT,
  TEMPLATE_SEPTIKS_HEADERS,
  TEMPLATE_SEPTIKS_LABELS,
  formatTemplateDate,
} from '@/modules/estimates/config/estimateTemplates.config'



const router = useRouter()
const ability = useAbility()
const canCreate = computed(() => ability.can('create', 'estimate_templates'))
const canEdit = computed(() => ability.can('edit', 'estimate_templates'))
const canDelete = computed(() => ability.can('delete', 'estimate_templates'))

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

} = useTableInfinite<EstimateTemplateSeptik>({

  endpoint: 'estimate-templates/septiks',

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
  if (!canCreate.value) return

  router.push({ path: '/estimate-templates/septiks/new' })

}



const handleEdit = (row: EstimateTemplateSeptik) => {
  if (!canEdit.value) return

  router.push({ path: `/estimate-templates/septiks/${row.id}` })

}



const handleDelete = async (row: EstimateTemplateSeptik) => {
  if (!canDelete.value) return

  if (!row?.id) return

  if (!window.confirm(TEMPLATE_SEPTIKS_LABELS.confirmDelete)) return

  await $api(`/estimate-templates/septiks/${row.id}`, { method: 'DELETE' })

  await reset()

}



const totalLabel = computed(() => Number(totalRecords.value ?? 0).toLocaleString('ru-RU'))



const formatDate = formatTemplateDate

const formatTemplateRefs = (row: EstimateTemplateSeptik) => {
  if (Array.isArray(row.template_titles) && row.template_titles.length) {
    return row.template_titles.join(', ')
  }

  if (Array.isArray(row.template_ids) && row.template_ids.length) {
    return row.template_ids.map(templateId => `#${templateId}`).join(', ')
  }

  return `#${row.template_id ?? TEMPLATE_EMPTY_TEXT}`
}

const formatTemplateIds = (templateIds: number[]) =>
  templateIds.map(templateId => `#${templateId}`).join(', ')



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

            :placeholder="TEMPLATE_SEPTIKS_LABELS.searchPlaceholder"

          />

        </div>

        <div class="flex items-center gap-2">

          <TableTotalLabel :label="TEMPLATE_SEPTIKS_LABELS.total" :value="totalLabel" />

          <Button

            v-if="canCreate"
            :label="TEMPLATE_SEPTIKS_LABELS.create"

            icon="pi pi-plus"

            size="small"

            @click="handleCreate"

          />

        </div>

      </div>

    </template>



    <Column field="id" :header="TEMPLATE_SEPTIKS_HEADERS.id" style="inline-size: 6ch;" />

    <Column field="title" :header="TEMPLATE_SEPTIKS_HEADERS.title" />

    <Column field="template_title" :header="TEMPLATE_SEPTIKS_HEADERS.templateTitle">

      <template #body="{ data: row }">

        {{ Array.isArray(row.template_titles) && row.template_titles.length

          ? row.template_titles.join(', ')

          : Array.isArray(row.template_ids) && row.template_ids.length

            ? formatTemplateIds(row.template_ids)

            : `#${row.template_id ?? TEMPLATE_EMPTY_TEXT}` }}

      </template>

    </Column>

    <Column field="skus" :header="TEMPLATE_SEPTIKS_HEADERS.skus" style="inline-size: 14ch;">

      <template #body="{ data: row }">

        {{ Array.isArray(row.skus) ? row.skus.length : 0 }}

      </template>

    </Column>

    <Column field="updated_at" :header="TEMPLATE_SEPTIKS_HEADERS.updatedAt" style="inline-size: 12ch;">

      <template #body="{ data: row }">

        {{ formatDate(row.updated_at) }}

      </template>

    </Column>

    <Column header="" style="inline-size: 10ch;">

      <template #body="{ data: row }">

        <div class="flex items-center gap-1">

          <Button

            v-if="canEdit"
            icon="pi pi-pencil"

            text

            :aria-label="TEMPLATE_SEPTIKS_LABELS.editAria"

            @click="handleEdit(row)"

          />

          <Button

            v-if="canDelete"
            icon="pi pi-trash"

            text

            severity="danger"

            :aria-label="TEMPLATE_SEPTIKS_LABELS.deleteAria"

            @click="handleDelete(row)"

          />

        </div>

      </template>

    </Column>



    <template #empty>

      <div class="text-center py-6 text-muted">{{ TEMPLATE_SEPTIKS_LABELS.empty }}</div>

    </template>



    <template #loading>

      <div class="text-center py-6 text-muted">{{ TEMPLATE_SEPTIKS_LABELS.loading }}</div>

    </template>

  </DataTable>

</template>
