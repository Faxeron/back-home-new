<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useAbility } from '@casl/vue'
import { useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import { useTableInfinite } from '@/composables/useTableLazy'
import { deleteContractTemplate } from '@/modules/production/api/contractTemplates.api'
import type { ContractTemplate } from '@/modules/production/types/contract-templates.types'
import {
  CONTRACT_TEMPLATE_HEADERS,
  CONTRACT_TEMPLATE_LABELS,
  formatAdvanceMode,
  formatDocumentType,
  formatTemplateDate,
} from '@/modules/production/config/contractTemplates.config'

const router = useRouter()
const ability = useAbility()
const tableRef = ref<any>(null)
const scrollHeight = ref('700px')
const search = ref('')
const canCreate = computed(() => ability.can('create', 'contract_templates'))
const canEdit = computed(() => ability.can('edit', 'contract_templates'))
const canDelete = computed(() => ability.can('delete', 'contract_templates'))

const params = computed(() => ({
  q: search.value || undefined,
}))

const {
  data,
  total: totalRecords,
  loading,
  reset,
  virtualScrollerOptions,
} = useTableInfinite<ContractTemplate>({
  endpoint: 'contract-templates',
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

const handleResize = () => updateScrollHeight()

const handleCreate = () => {
  if (!canCreate.value) return
  router.push({ path: '/operations/contracts/templates/new' })
}

const handleEdit = (row: ContractTemplate) => {
  if (!canEdit.value) return
  router.push({ path: `/operations/contracts/templates/${row.id}` })
}

const handleDelete = async (row: ContractTemplate) => {
  if (!canDelete.value) return
  if (!row?.id) return
  if (!window.confirm(CONTRACT_TEMPLATE_LABELS.confirmDelete)) return
  await deleteContractTemplate(row.id)
  await reset()
}

const totalLabel = computed(() => Number(totalRecords.value ?? 0).toLocaleString('ru-RU'))
const formatProductTypes = (row: ContractTemplate) =>
  row.product_types?.map(productType => productType.name).join(', ') || '-'

onMounted(async () => {
  await reset()
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
            :placeholder="CONTRACT_TEMPLATE_LABELS.searchPlaceholder"
          />
        </div>
        <div class="flex items-center gap-2">
          <TableTotalLabel :label="CONTRACT_TEMPLATE_LABELS.total" :value="totalLabel" />
          <Button
            v-if="canCreate"
            :label="CONTRACT_TEMPLATE_LABELS.create"
            icon="pi pi-plus"
            size="small"
            @click="handleCreate"
          />
        </div>
      </div>
    </template>

    <Column field="id" :header="CONTRACT_TEMPLATE_HEADERS.id" style="inline-size: 6ch;" />
    <Column field="name" :header="CONTRACT_TEMPLATE_HEADERS.name" />
    <Column field="short_name" :header="CONTRACT_TEMPLATE_HEADERS.shortName" style="inline-size: 16ch;" />
    <Column :header="CONTRACT_TEMPLATE_HEADERS.documentType" style="inline-size: 14ch;">
      <template #body="{ data: row }">
        {{ formatDocumentType(row.document_type) }}
      </template>
    </Column>
    <Column :header="CONTRACT_TEMPLATE_HEADERS.productTypes">
      <template #body="{ data: row }">
        <span>
          {{ formatProductTypes(row) }}
        </span>
      </template>
    </Column>
    <Column :header="CONTRACT_TEMPLATE_HEADERS.advance" style="inline-size: 16ch;">
      <template #body="{ data: row }">
        {{ formatAdvanceMode(row) }}
      </template>
    </Column>
    <Column field="is_active" :header="CONTRACT_TEMPLATE_HEADERS.isActive" style="inline-size: 10ch;">
      <template #body="{ data: row }">
        {{ row.is_active ? 'Да' : 'Нет' }}
      </template>
    </Column>
    <Column field="updated_at" :header="CONTRACT_TEMPLATE_HEADERS.updatedAt" style="inline-size: 12ch;">
      <template #body="{ data: row }">
        {{ formatTemplateDate(row.updated_at) }}
      </template>
    </Column>
    <Column header="" style="inline-size: 10ch;">
      <template #body="{ data: row }">
        <div class="flex items-center gap-1">
          <Button
            v-if="canEdit"
            icon="pi pi-pencil"
            text
            :aria-label="CONTRACT_TEMPLATE_LABELS.editAria"
            @click="handleEdit(row)"
          />
          <Button
            v-if="canDelete"
            icon="pi pi-trash"
            text
            severity="danger"
            :aria-label="CONTRACT_TEMPLATE_LABELS.deleteAria"
            @click="handleDelete(row)"
          />
        </div>
      </template>
    </Column>

    <template #empty>
      <div class="text-center py-6 text-muted">{{ CONTRACT_TEMPLATE_LABELS.empty }}</div>
    </template>

    <template #loading>
      <div class="text-center py-6 text-muted">{{ CONTRACT_TEMPLATE_LABELS.loading }}</div>
    </template>
  </DataTable>
</template>
