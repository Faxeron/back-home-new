<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
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
const ability = useAbility()
const canDeleteContract = computed(() => ability.can('delete', 'contracts'))
const canEditContract = computed(() => ability.can('edit', 'contracts'))
const canCreateFinance = computed(() => ability.can('create', 'finance'))
const tableRef = ref<any>(null)
const scrollHeight = ref('700px')
const reloadRef = ref<() => void>(() => {})
const receiptDialogOpen = ref(false)
const spendingDialogOpen = ref(false)
const selectedContract = ref<Contract | null>(null)
const confirmDeleteOpen = ref(false)
const contractToDelete = ref<Contract | null>(null)
const deleting = ref(false)
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

const requestDeleteContract = (row: Contract) => {
  contractToDelete.value = row
  confirmDeleteOpen.value = true
}

const deleteContract = async () => {
  const row = contractToDelete.value
  if (!row) return
  deleting.value = true
  try {
    await $api(`contracts/${row.id}`, { method: 'DELETE' })
    confirmDeleteOpen.value = false
    contractToDelete.value = null
    showSnackbar('Договор удален.', 'success')
    resetData()
  } catch (error: any) {
    const message =
      error?.data?.message ??
      error?.response?.data?.message ??
      error?.response?._data?.message ??
      'Не удалось удалить договор.'
    showSnackbar(message, 'error')
  } finally {
    deleting.value = false
  }
}

const handleAction = (payload: { action: string; row: Contract }) => {
  if (payload.action === 'contract' || payload.action === 'edit') {
    router.push({ path: '/operations/contracts/' + payload.row.id })
  }
  if (payload.action === 'receipt') {
    if (!canCreateFinance.value) return
    selectedContract.value = payload.row
    receiptDialogOpen.value = true
  }
  if (payload.action === 'spending') {
    if (!canCreateFinance.value) return
    selectedContract.value = payload.row
    spendingDialogOpen.value = true
  }
  if (payload.action === 'delete') {
    if (!canDeleteContract.value) return
    requestDeleteContract(payload.row)
  }
}

const applyStatusUpdate = async ({ row, statusId: nextId }: { row: Contract; statusId: number | null }) => {
  if (!canEditContract.value) return
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
    :canDelete="canDeleteContract"
    :canEditStatus="canEditContract"
    :canFinance="canCreateFinance"
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

  <VDialog v-model="confirmDeleteOpen" max-width="420">
    <VCard>
      <VCardTitle>Удалить договор?</VCardTitle>
      <VCardText>Договор и связанные данные будут удалены без восстановления.</VCardText>
      <VCardActions>
        <VSpacer />
        <VBtn variant="text" @click="confirmDeleteOpen = false">Отмена</VBtn>
        <VBtn color="error" :loading="deleting" @click="deleteContract">Удалить</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VSnackbar v-model="snackbarOpen" :color="snackbarColor" location="bottom end" :timeout="2500">
    {{ snackbarText }}
  </VSnackbar>
</template>
