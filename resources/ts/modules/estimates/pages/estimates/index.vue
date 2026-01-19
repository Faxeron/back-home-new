<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import EstimatesTable from '@/modules/estimates/components/EstimatesTable.vue'
import CreateContractsDialog from '@/modules/estimates/components/CreateContractsDialog.vue'
import { useTableInfinite } from '@/composables/useTableLazy'
import { useEstimateFilters } from '@/modules/estimates/composables/useEstimateFilters'
import { useTableScrollHeight } from '@/composables/useTableScrollHeight'
import { deleteEstimate } from '@/modules/estimates/api/estimate.api'
import type { Estimate } from '@/modules/estimates/types/estimates.types'

const router = useRouter()
const tableRef = ref<any>(null)
const createContractsOpen = ref(false)
const selectedEstimate = ref<Estimate | null>(null)

const {
  search,
  dateRange,
  params,
  resetFilters,
} = useEstimateFilters()

const {
  data,
  total: totalRecords,
  loading,
  reset,
  virtualScrollerOptions,
} = useTableInfinite<Estimate>({
  endpoint: 'estimates',
  params: () => params.value,
  perPage: 50,
  rowHeight: 52,
})

const { scrollHeight } = useTableScrollHeight(tableRef)

const handleReset = () => {
  resetFilters()
  reset()
}

const handleOpen = (row: Estimate) => {
  router.push({ path: `/estimates/${row.id}/edit` })
}

const handleCreate = () => {
  router.push({ path: '/estimates/new' })
}

const handleDelete = async (row: Estimate) => {
  const confirmed = window.confirm('Удалить смету?')
  if (!confirmed) return
  try {
    await deleteEstimate(row.id)
    reset()
  } catch (error: any) {
    const message = error?.response?.data?.message ?? 'Не удалось удалить смету.'
    window.alert(message)
  }
}

const handleCreateContract = (row: Estimate) => {
  selectedEstimate.value = row
  createContractsOpen.value = true
}

const handleOpenContract = (row: Estimate) => {
  if (!row.contract_id) return
  router.push({ path: `/operations/contracts/${row.contract_id}` })
}

const handleContractsCreated = () => {
  reset()
}

onMounted(async () => {
  await reset()
})

watch([search, dateRange], () => {
  reset()
})
</script>

<template>
  <EstimatesTable
    ref="tableRef"
    v-model:search="search"
    v-model:dateRange="dateRange"
    :rows="data"
    :loading="loading"
    :totalRecords="totalRecords"
    :scrollHeight="scrollHeight"
    :virtualScrollerOptions="virtualScrollerOptions"
    @reset="handleReset"
    @open="handleOpen"
    @open-contract="handleOpenContract"
    @delete="handleDelete"
    @create-contract="handleCreateContract"
    @create="handleCreate"
  />
  <CreateContractsDialog
    v-model="createContractsOpen"
    :estimate="selectedEstimate"
    @created="handleContractsCreated"
  />
</template>
