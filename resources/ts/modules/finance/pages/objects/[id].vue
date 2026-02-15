<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTableInfinite } from '@/composables/useTableLazy'
import { getFinanceObject, listFinanceObjectTypes } from '@/modules/finance/api/finance-objects.api'
import type { FinanceObject, FinanceObjectTypeView, Transaction } from '@/types/finance'
import { formatDateShort, formatSum } from '@/utils/formatters/finance'

const route = useRoute()
const router = useRouter()
const objectId = computed(() => Number(route.params.id))

const activeTab = ref('overview')
const loadingObject = ref(false)
const object = ref<FinanceObject | null>(null)
const typeCatalog = ref<FinanceObjectTypeView[]>([])
const errorMessage = ref('')

const {
  data: transactions,
  total: transactionsTotal,
  loading: transactionsLoading,
  hasMore: hasMoreTransactions,
  loadMore: loadMoreTransactions,
  reset: reloadTransactions,
} = useTableInfinite<Transaction>({
  endpoint: `finance/objects/${objectId.value}/transactions`,
  include: 'cashbox,counterparty,transactionType,paymentMethod,financeObject,financeObjectAllocations',
  perPage: 100,
  rowHeight: 50,
})

const loadObject = async () => {
  loadingObject.value = true
  errorMessage.value = ''
  try {
    const response: any = await getFinanceObject(objectId.value)
    object.value = (response?.data ?? response) as FinanceObject
  } catch (error: any) {
    errorMessage.value =
      error?.data?.message ??
      error?.response?.data?.message ??
      'Failed to load finance object.'
  } finally {
    loadingObject.value = false
  }
}

const loadTypeCatalog = async () => {
  try {
    const response: any = await listFinanceObjectTypes({ include_disabled: 1 })
    const rows = Array.isArray(response?.data) ? response.data : Array.isArray(response) ? response : []
    typeCatalog.value = rows
  } catch {
    typeCatalog.value = []
  }
}

const activeTypeMeta = computed(() => {
  const key = object.value?.type
  if (!key) return null
  return typeCatalog.value.find(item => item.key === key) ?? null
})

const typeLabel = computed(() => activeTypeMeta.value?.name ?? object.value?.type ?? '')
const typeDisabled = computed(() => activeTypeMeta.value ? !activeTypeMeta.value.is_enabled : false)

onMounted(async () => {
  if (!objectId.value) {
    router.push({ path: '/operations/finance-objects' })
    return
  }

  await Promise.all([loadObject(), loadTypeCatalog(), reloadTransactions()])
})
</script>

<template>
  <VCard>
    <VCardTitle class="d-flex align-center justify-space-between gap-3">
      <div class="d-flex flex-column">
        <span class="text-h6">{{ object?.name ?? `Finance Object #${objectId}` }}</span>
        <span class="text-body-2 text-medium-emphasis">
          {{ typeLabel }} ({{ object?.type ?? '' }}) | {{ object?.status ?? '' }} | {{ object?.code ?? '' }}
        </span>
        <VChip v-if="typeDisabled" size="small" color="warning" variant="tonal" class="mt-1">Тип отключен</VChip>
      </div>
      <VBtn variant="text" @click="router.push('/operations/finance-objects')">Back</VBtn>
    </VCardTitle>

    <VCardText class="d-flex flex-column gap-4">
      <div v-if="errorMessage" class="text-error">{{ errorMessage }}</div>
      <VProgressLinear v-if="loadingObject" indeterminate color="primary" />

      <div class="d-grid grid-cols-1 md:grid-cols-4 gap-3">
        <VCard variant="tonal">
          <VCardText>
            <div class="text-caption text-medium-emphasis">Income Fact</div>
            <div class="text-h6">{{ formatSum({ amount: String(object?.kpi?.income_fact ?? 0), currency: 'RUB' }) }}</div>
          </VCardText>
        </VCard>
        <VCard variant="tonal">
          <VCardText>
            <div class="text-caption text-medium-emphasis">Expense Fact</div>
            <div class="text-h6">{{ formatSum({ amount: String(object?.kpi?.expense_fact ?? 0), currency: 'RUB' }) }}</div>
          </VCardText>
        </VCard>
        <VCard variant="tonal">
          <VCardText>
            <div class="text-caption text-medium-emphasis">Net Fact</div>
            <div class="text-h6">{{ formatSum({ amount: String(object?.kpi?.net_fact ?? 0), currency: 'RUB' }) }}</div>
          </VCardText>
        </VCard>
        <VCard variant="tonal">
          <VCardText>
            <div class="text-caption text-medium-emphasis">Debitor / Creditor</div>
            <div class="text-h6">
              {{ formatSum({ amount: String(object?.kpi?.debitor ?? 0), currency: 'RUB' }) }}
              /
              {{ formatSum({ amount: String(object?.kpi?.creditor ?? 0), currency: 'RUB' }) }}
            </div>
          </VCardText>
        </VCard>
      </div>

      <VTabs v-model="activeTab">
        <VTab value="overview">Overview</VTab>
        <VTab value="money">Money</VTab>
        <VTab value="documents">Documents</VTab>
        <VTab value="history">History</VTab>
      </VTabs>

      <VWindow v-model="activeTab">
        <VWindowItem value="overview">
          <div class="d-flex flex-column gap-2">
            <div><strong>Counterparty:</strong> {{ object?.counterparty?.name ?? '-' }}</div>
            <div><strong>Period:</strong> {{ formatDateShort(object?.date_from) }} - {{ formatDateShort(object?.date_to ?? null) }}</div>
            <div><strong>Description:</strong> {{ object?.description ?? '-' }}</div>
          </div>
        </VWindowItem>

        <VWindowItem value="money">
          <div class="text-body-2 text-medium-emphasis mb-2">
            Transactions: {{ Number(transactionsTotal ?? 0).toLocaleString('ru-RU') }}
          </div>
          <VProgressLinear v-if="transactionsLoading" indeterminate color="primary" />
          <VTable density="compact">
            <thead>
              <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Counterparty</th>
                <th>Comment</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in transactions" :key="row.id">
                <td>{{ row.id }}</td>
                <td>{{ formatDateShort(row.created_at ?? row.date_is_paid ?? null) }}</td>
                <td>{{ row.transaction_type?.name ?? '' }}</td>
                <td>{{ formatSum(row.sum) }}</td>
                <td>{{ row.counterparty?.name ?? '' }}</td>
                <td>{{ row.notes ?? '' }}</td>
              </tr>
              <tr v-if="!transactions.length && !transactionsLoading">
                <td colspan="6" class="text-center py-4 text-medium-emphasis">No transactions</td>
              </tr>
            </tbody>
          </VTable>
          <div class="d-flex justify-center mt-3">
            <VBtn v-if="hasMoreTransactions" variant="text" :loading="transactionsLoading" @click="loadMoreTransactions">Load More</VBtn>
          </div>
        </VWindowItem>

        <VWindowItem value="documents">
          <div class="text-medium-emphasis">Contract and files integration stays compatible with existing contract documents.</div>
        </VWindowItem>

        <VWindowItem value="history">
          <div class="text-medium-emphasis">History log will be expanded in next iteration.</div>
        </VWindowItem>
      </VWindow>
    </VCardText>
  </VCard>
</template>


