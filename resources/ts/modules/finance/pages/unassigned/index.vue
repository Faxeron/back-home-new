<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useTableInfinite } from '@/composables/useTableLazy'
import { bulkAssignUnassignedTransactions } from '@/modules/finance/api/transactions.api'
import type { Transaction } from '@/types/finance'
import { useDictionariesStore } from '@/stores/dictionaries'
import { formatDateShort, formatSum } from '@/utils/formatters/finance'

const dictionaries = useDictionariesStore()
const selectedIds = ref<number[]>([])
const selectedFinanceObjectId = ref<number | null>(null)
const assigning = ref(false)
const errorMessage = ref('')

const {
  data,
  total,
  loading,
  hasMore,
  loadMore,
  reset,
} = useTableInfinite<Transaction>({
  endpoint: 'finance/transactions/unassigned',
  include: 'cashbox,counterparty,transactionType,paymentMethod',
  perPage: 100,
  rowHeight: 50,
})

const allChecked = computed({
  get: () => data.value.length > 0 && selectedIds.value.length === data.value.length,
  set: value => {
    if (value) {
      selectedIds.value = data.value.map(item => item.id)
    } else {
      selectedIds.value = []
    }
  },
})

const toggleRow = (id: number, checked: boolean) => {
  if (checked) {
    if (!selectedIds.value.includes(id)) selectedIds.value.push(id)
    return
  }

  selectedIds.value = selectedIds.value.filter(item => item !== id)
}

const assignSelected = async () => {
  if (!selectedFinanceObjectId.value || !selectedIds.value.length) return
  assigning.value = true
  errorMessage.value = ''
  try {
    await bulkAssignUnassignedTransactions({
      finance_object_id: selectedFinanceObjectId.value,
      transaction_ids: selectedIds.value,
    })
    selectedIds.value = []
    await reset()
  } catch (error: any) {
    errorMessage.value =
      error?.data?.message ??
      error?.response?.data?.message ??
      'Failed to assign transactions.'
  } finally {
    assigning.value = false
  }
}

onMounted(async () => {
  await dictionaries.loadFinanceObjects()
  await reset()
})
</script>

<template>
  <VCard>
    <VCardTitle>Unassigned Transactions</VCardTitle>
    <VCardText class="d-flex flex-column gap-3">
      <div class="d-flex flex-wrap items-center gap-3">
        <VSelect
          v-model="selectedFinanceObjectId"
          :items="dictionaries.financeObjects"
          item-title="name"
          item-value="id"
          label="Finance Object"
          style="max-inline-size: 360px;"
          clearable
          hide-details
        />
        <VBtn
          color="primary"
          :disabled="!selectedFinanceObjectId || !selectedIds.length"
          :loading="assigning"
          @click="assignSelected"
        >
          Assign Selected
        </VBtn>
      </div>

      <div v-if="errorMessage" class="text-error text-sm">{{ errorMessage }}</div>
      <div class="text-body-2 text-medium-emphasis">
        Total: {{ Number(total ?? 0).toLocaleString('ru-RU') }}
      </div>

      <VProgressLinear v-if="loading" indeterminate color="primary" />

      <VTable density="compact">
        <thead>
          <tr>
            <th style="inline-size: 36px;">
              <VCheckboxBtn v-model="allChecked" />
            </th>
            <th>ID</th>
            <th>Date</th>
            <th>Type</th>
            <th>Amount</th>
            <th>Counterparty</th>
            <th>Comment</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in data" :key="row.id">
            <td>
              <VCheckboxBtn
                :model-value="selectedIds.includes(row.id)"
                @update:model-value="toggleRow(row.id, Boolean($event))"
              />
            </td>
            <td>{{ row.id }}</td>
            <td>{{ formatDateShort(row.created_at ?? row.date_is_paid ?? null) }}</td>
            <td>{{ row.transaction_type?.name ?? '' }}</td>
            <td>{{ formatSum(row.sum) }}</td>
            <td>{{ row.counterparty?.name ?? '' }}</td>
            <td>{{ row.notes ?? '' }}</td>
          </tr>
          <tr v-if="!data.length && !loading">
            <td colspan="7" class="text-center py-4 text-medium-emphasis">No unassigned transactions</td>
          </tr>
        </tbody>
      </VTable>

      <div class="d-flex justify-center py-2">
        <VBtn v-if="hasMore" :loading="loading" variant="text" @click="loadMore">Load More</VBtn>
      </div>
    </VCardText>
  </VCard>
</template>

