<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import TransactionsTable from '@/modules/finance/components/TransactionsTable.vue'
import { useTableInfinite } from '@/composables/useTableLazy'
import { useTransactionFilters } from '@/modules/finance/composables/useTransactionFilters'
import { TRANSACTION_TABLE } from '@/modules/finance/config/transactionsTable.config'
import { useDictionariesStore } from '@/stores/dictionaries'
import { $api } from '@/utils/api'
import type { Transaction } from '@/types/finance'

const dictionaries = useDictionariesStore()
const userData = useCookie<any>('userData')
const isAdmin = computed(() => String(userData.value?.role ?? '').toLowerCase() === 'admin')
const tableRef = ref<any>(null)
const scrollHeight = ref('700px')
const reloadRef = ref<() => void>(() => {})
const confirmDeleteOpen = ref(false)
const deleting = ref(false)
const transactionToDelete = ref<Transaction | null>(null)
const snackbarOpen = ref(false)
const snackbarText = ref('')
const snackbarColor = ref<'success' | 'error'>('success')

const { filters, serverParams, resetFilters, handleSort } = useTransactionFilters({
  onChange: () => reloadRef.value(),
})

const {
  data,
  total: totalRecords,
  loading,
  reset: resetData,
  virtualScrollerOptions,
} = useTableInfinite<Transaction>({
  endpoint: 'finance/transactions',
  include: TRANSACTION_TABLE.include,
  perPage: TRANSACTION_TABLE.perPage,
  rowHeight: TRANSACTION_TABLE.rowHeight,
  params: () => serverParams.value,
})

reloadRef.value = () => {
  resetData()
}

const showSnackbar = (text: string, color: 'success' | 'error' = 'success') => {
  snackbarText.value = text
  snackbarColor.value = color
  snackbarOpen.value = true
}

const requestDeleteTransaction = (row: Transaction) => {
  transactionToDelete.value = row
  confirmDeleteOpen.value = true
}

const deleteTransaction = async () => {
  const row = transactionToDelete.value
  if (!row) return
  deleting.value = true
  try {
    await $api(`finance/transactions/${row.id}`, { method: 'DELETE' })
    confirmDeleteOpen.value = false
    transactionToDelete.value = null
    showSnackbar('Транзакция удалена.', 'success')
    resetData()
  } catch (error: any) {
    const message =
      error?.data?.message ??
      error?.response?.data?.message ??
      error?.response?._data?.message ??
      'Не удалось удалить транзакцию.'
    showSnackbar(message, 'error')
  } finally {
    deleting.value = false
  }
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

onMounted(async () => {
  await Promise.all([
    dictionaries.loadCashBoxes(true),
    dictionaries.loadTransactionTypes(),
    dictionaries.loadPaymentMethods(),
  ])
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
  <TransactionsTable
    ref="tableRef"
    v-model:filters="filters"
    :rows="data"
    :loading="loading"
    :totalRecords="totalRecords"
    :scrollHeight="scrollHeight"
    :virtualScrollerOptions="virtualScrollerOptions"
    :canDelete="isAdmin"
    @sort="handleSort"
    @reset-filters="resetFilters"
    @delete="requestDeleteTransaction"
  />

  <VDialog v-model="confirmDeleteOpen" max-width="420">
    <VCard>
      <VCardTitle>Удалить транзакцию?</VCardTitle>
      <VCardText>Будут удалены связанный приход/расход и привязки.</VCardText>
      <VCardActions>
        <VSpacer />
        <VBtn variant="text" @click="confirmDeleteOpen = false">Отмена</VBtn>
        <VBtn color="error" :loading="deleting" @click="deleteTransaction">Удалить</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VSnackbar v-model="snackbarOpen" :color="snackbarColor" location="bottom end" :timeout="2500">
    {{ snackbarText }}
  </VSnackbar>
</template>
