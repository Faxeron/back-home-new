<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { $api } from '@/utils/api'
import { formatSum } from '@/utils/formatters/finance'
import { TRANSACTION_TABLE } from '@/modules/finance/config/transactionsTable.config'
import NewCashboxesBalance, { type CashboxBalanceRow } from '@/views/dashboards/new/NewCashboxesBalance.vue'
import NewRecentTransactions from '@/views/dashboards/new/NewRecentTransactions.vue'
import type { Transaction } from '@/types/finance'

definePage({
  meta: {
    action: 'view',
    subject: 'finance',
  },
})

const cashboxes = ref<CashboxBalanceRow[]>([])
const cashboxesLoading = ref(false)
const cashboxesError = ref('')

const transactions = ref<Transaction[]>([])
const transactionsLoading = ref(false)
const transactionsError = ref('')
const transactionsTotal = ref<number | null>(null)

const updatedAt = ref<Date | null>(null)

const cashboxesTotal = computed(() =>
  cashboxes.value.reduce((sum, row) => sum + (Number(row.balance ?? 0) || 0), 0),
)

const recentIncomes = computed(() => {
  return transactions.value.reduce((sum, row) => {
    const sign = Number(row.transaction_type?.sign ?? 0)
    if (sign <= 0) return sum
    return sum + (Number((row.sum as any)?.amount ?? row.sum ?? 0) || 0)
  }, 0)
})

const recentExpenses = computed(() => {
  return transactions.value.reduce((sum, row) => {
    const sign = Number(row.transaction_type?.sign ?? 0)
    if (sign >= 0) return sum
    return sum + (Number((row.sum as any)?.amount ?? row.sum ?? 0) || 0)
  }, 0)
})

const loadCashboxes = async () => {
  cashboxesLoading.value = true
  cashboxesError.value = ''
  try {
    const response: any = await $api('finance/cashboxes')
    cashboxes.value = response?.data ?? []
  } catch (error: any) {
    cashboxesError.value = error?.response?.data?.message ?? 'Не удалось загрузить баланс касс.'
    cashboxes.value = []
  } finally {
    cashboxesLoading.value = false
  }
}

const loadRecentTransactions = async () => {
  transactionsLoading.value = true
  transactionsError.value = ''
  try {
    const response: any = await $api('finance/transactions', {
      query: {
        page: 1,
        per_page: 10,
        include: TRANSACTION_TABLE.include,
        sort: 'created_at',
        direction: 'desc',
      },
    })

    transactions.value = response?.data ?? []
    transactionsTotal.value = typeof response?.meta?.total === 'number' ? response.meta.total : null
  } catch (error: any) {
    transactionsError.value = error?.response?.data?.message ?? 'Не удалось загрузить транзакции.'
    transactions.value = []
    transactionsTotal.value = null
  } finally {
    transactionsLoading.value = false
  }
}

const refreshAll = async () => {
  await Promise.all([loadCashboxes(), loadRecentTransactions()])
  updatedAt.value = new Date()
}

onMounted(refreshAll)
</script>

<template>
  <VRow class="match-height">
    <VCol cols="12">
      <div class="d-flex align-center justify-space-between flex-wrap gap-3">
        <div>
          <h4 class="text-h4 mb-1">
            Dashboards NEW
          </h4>
          <div class="text-medium-emphasis">
            Финансы: баланс касс и последние транзакции
          </div>
        </div>

        <div class="d-flex align-center gap-2">
          <VChip
            v-if="updatedAt"
            size="small"
            variant="tonal"
            color="secondary"
          >
            Обновлено: {{ updatedAt.toLocaleString('ru-RU') }}
          </VChip>

          <VBtn
            color="primary"
            variant="tonal"
            @click="refreshAll"
          >
            Обновить
          </VBtn>
        </div>
      </div>
    </VCol>

    <VCol
      cols="12"
      md="4"
    >
      <CardStatisticsVerticalSimple
        title="Итого по кассам"
        icon="tabler-cash"
        color="primary"
        :stats="`${formatSum(cashboxesTotal)} RUB`"
      />
    </VCol>

    <VCol
      cols="12"
      md="4"
    >
      <CardStatisticsVerticalSimple
        title="Приходы (последние 10)"
        icon="tabler-arrow-down-right"
        color="success"
        :stats="`${formatSum(recentIncomes)} RUB`"
      />
    </VCol>

    <VCol
      cols="12"
      md="4"
    >
      <CardStatisticsVerticalSimple
        title="Расходы (последние 10)"
        icon="tabler-arrow-up-right"
        color="error"
        :stats="`${formatSum(recentExpenses)} RUB`"
      />
    </VCol>

    <VCol
      cols="12"
      lg="5"
    >
      <NewCashboxesBalance
        :rows="cashboxes"
        :total="cashboxesTotal"
        :loading="cashboxesLoading"
        :error="cashboxesError"
        @refresh="loadCashboxes"
      />
    </VCol>

    <VCol
      cols="12"
      lg="7"
    >
      <NewRecentTransactions
        :rows="transactions"
        :total="transactionsTotal"
        :loading="transactionsLoading"
        :error="transactionsError"
        @refresh="loadRecentTransactions"
      />
    </VCol>
  </VRow>
</template>

