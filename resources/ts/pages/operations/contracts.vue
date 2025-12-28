<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import ContractsTable from '@/components/tables/contracts/ContractsTable.vue'
import { useTableInfinite } from '@/composables/useTableLazy'
import { useContractsQuery } from '@/composables/useContractsQuery'
import { CONTRACTS_TABLE } from '@/config/tables/contracts'
import { useDictionariesStore } from '@/stores/dictionaries'
import { $api } from '@/utils/api'
import type { Contract } from '@/types/finance'

const dictionaries = useDictionariesStore()
const tableRef = ref<any>(null)
const scrollHeight = ref('700px')
const reloadRef = ref<() => void>(() => {})

const { search, statusId, serverParams, reset } = useContractsQuery({
  onChange: () => reloadRef.value(),
})

const {
  data,
  total: totalRecords,
  loading,
  reset: resetData,
  virtualScrollerOptions,
} = useTableInfinite<Contract>({
  endpoint: 'contracts',
  perPage: CONTRACTS_TABLE.perPage,
  rowHeight: CONTRACTS_TABLE.rowHeight,
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

const applyStatusUpdate = async ({ row, statusId: nextId }: { row: Contract; statusId: number | null }) => {
  if (nextId === null || row.contract_status_id === nextId) return
  const previousId = row.contract_status_id ?? null
  const previousStatus = row.status ? { ...row.status } : undefined

  row.contract_status_id = nextId
  const status = dictionaries.contractStatuses.find(item => Number(item.id) === Number(nextId))
  if (status) {
    row.status = {
      id: Number(status.id),
      name: status.name,
      color: status.color,
    }
  }

  try {
    const response: any = await $api(`contracts/${row.id}/status`, {
      method: 'PATCH',
      body: { contract_status_id: nextId },
    })
    const payload = response?.data ?? response
    if (payload?.contract_status_id !== undefined) {
      row.contract_status_id = payload.contract_status_id
    }
    if (payload?.status) {
      row.status = payload.status
    }
  } catch (error) {
    row.contract_status_id = previousId
    row.status = previousStatus
    console.error('Failed to update contract status', error)
  }
}

onMounted(async () => {
  await dictionaries.loadContractStatuses(true)
  await resetData()
  await nextTick()
  updateScrollHeight()
  window.addEventListener('resize', handleResize)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
})
</script>

<template>
  <ContractsTable
    ref="tableRef"
    v-model:search="search"
    v-model:statusId="statusId"
    :rows="data"
    :loading="loading"
    :totalRecords="totalRecords"
    :scrollHeight="scrollHeight"
    :virtualScrollerOptions="virtualScrollerOptions"
    :statuses="dictionaries.contractStatuses"
    @status-change="applyStatusUpdate"
    @reset="reset"
  />
</template>
