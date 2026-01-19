<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ContractsTable from '@/modules/production/components/contracts/ContractsTable.vue'
import ContractReceiptDialog from '@/modules/production/components/contracts/ContractReceiptDialog.vue'
import ContractSpendingDialog from '@/modules/production/components/contracts/ContractSpendingDialog.vue'
import { useTableInfinite } from '@/composables/useTableLazy'
import { useContractsQuery } from '@/modules/production/composables/useContractsQuery'
import { CONTRACTS_TABLE } from '@/modules/production/config/contractsTable.config'
import { useDictionariesStore } from '@/stores/dictionaries'
import { $api } from '@/utils/api'
import type { Contract } from '@/types/finance'

const dictionaries = useDictionariesStore()
const route = useRoute()
const router = useRouter()
const tableRef = ref<any>(null)
const scrollHeight = ref('700px')
const reloadRef = ref<() => void>(() => {})
const receiptDialogOpen = ref(false)
const spendingDialogOpen = ref(false)
const selectedContract = ref<Contract | null>(null)
const snackbarOpen = ref(false)
const snackbarText = ref('')
const snackbarColor = ref<'success' | 'error'>('success')

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

const showSnackbar = (text: string, color: 'success' | 'error' = 'success') => {
  snackbarText.value = text
  snackbarColor.value = color
  snackbarOpen.value = true
}

const handleAction = (payload: { action: string; row: Contract }) => {
  if (payload.action === 'contract' || payload.action === 'edit') {
    router.push({ path: '/operations/contracts/' + payload.row.id })
  }
  if (payload.action === 'receipt') {
    selectedContract.value = payload.row
    receiptDialogOpen.value = true
  }
  if (payload.action === 'spending') {
    selectedContract.value = payload.row
    spendingDialogOpen.value = true
  }
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
  if (route.query.toast === 'contract-deleted') {
    snackbarText.value = 'Договор удален.'
    snackbarColor.value = 'success'
    snackbarOpen.value = true
    await router.replace({ query: { ...route.query, toast: undefined } })
  }
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
    @action="handleAction"
  />

  <ContractReceiptDialog
    v-model="receiptDialogOpen"
    :contract="selectedContract"
    @created="showSnackbar('Приход добавлен.')"
  />

  <ContractSpendingDialog
    v-model="spendingDialogOpen"
    :contract="selectedContract"
    @created="showSnackbar('Расход добавлен.')"
  />

  <VSnackbar v-model="snackbarOpen" :color="snackbarColor" location="bottom end" :timeout="2500">
    {{ snackbarText }}
  </VSnackbar>
</template>
