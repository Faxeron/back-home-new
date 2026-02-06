<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { $api } from '@/utils/api'
import { formatDateShort, formatSum, statusLines } from '@/utils/formatters/finance'
import CashboxCell from '@/components/cashboxes/CashboxCell.vue'
import type { Transaction } from '@/types/finance'

definePage({
  meta: {
    action: 'view',
    subject: 'finance',
  },
})

type CashboxBalanceRow = {
  id: number
  name?: string | null
  balance?: number | null
  logo_url?: string | null
}

const cashboxes = ref<CashboxBalanceRow[]>([])
const cashboxesLoading = ref(false)
const cashboxesError = ref('')

const transactions = ref<Transaction[]>([])
const transactionsLoading = ref(false)
const transactionsError = ref('')

const cashboxesTotal = computed(() =>
  cashboxes.value.reduce((sum, row) => sum + (Number(row.balance ?? 0) || 0), 0),
)

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
        include: 'cashbox,counterparty,contract,transactionType,paymentMethod',
        sort: 'created_at',
        direction: 'desc',
      },
    })

    transactions.value = response?.data ?? []
  } catch (error: any) {
    transactionsError.value = error?.response?.data?.message ?? 'Не удалось загрузить транзакции.'
    transactions.value = []
  } finally {
    transactionsLoading.value = false
  }
}

const refreshAll = async () => {
  await Promise.all([loadCashboxes(), loadRecentTransactions()])
}

const formatSignedSum = (row: Transaction) => {
  const sign = Number(row.transaction_type?.sign ?? 0)
  const prefix = sign < 0 ? '-' : sign > 0 ? '+' : ''
  const currency = row.sum?.currency ?? 'RUB'

  // Transaction sums are stored as positive amounts, sign comes from transaction type.
  return `${prefix}${formatSum(row.sum)} ${currency}`
}

onMounted(refreshAll)
</script>

<template>
  <VCard>
    <VCardTitle class="d-flex align-center justify-space-between">
      <span>Дашборды NEW</span>
      <VBtn color="primary" variant="tonal" @click="refreshAll">Обновить</VBtn>
    </VCardTitle>

    <VCardText>
      <VRow class="match-height">
        <VCol cols="12" lg="4">
          <VCard variant="outlined" class="h-100">
            <VCardTitle class="d-flex align-center justify-space-between">
              <span>Баланс касс</span>
              <VBtn size="small" variant="text" @click="loadCashboxes">Обновить</VBtn>
            </VCardTitle>
            <VCardText>
              <div v-if="cashboxesError" class="text-sm mb-3" style="color: #b91c1c;">
                {{ cashboxesError }}
              </div>

              <DataTable
                :value="cashboxes"
                dataKey="id"
                class="p-datatable-sm"
                :loading="cashboxesLoading"
              >
                <Column field="name" header="Касса">
                  <template #body="{ data }">
                    <CashboxCell :cashbox="data" size="sm" />
                  </template>
                </Column>
                <Column field="balance" header="Баланс" style="inline-size: 16ch;">
                  <template #body="{ data }">
                    {{ formatSum(Number(data.balance ?? 0)) }}
                  </template>
                </Column>
                <template #empty>
                  <div class="text-center py-6 text-muted">Нет данных.</div>
                </template>
              </DataTable>

              <VDivider class="my-4" />
              <div class="d-flex justify-end text-sm font-semibold">
                Итог: {{ formatSum(cashboxesTotal) }}
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" lg="8">
          <VCard variant="outlined" class="h-100">
            <VCardTitle class="d-flex align-center justify-space-between">
              <span>Последние транзакции</span>
              <VBtn size="small" variant="text" @click="loadRecentTransactions">Обновить</VBtn>
            </VCardTitle>
            <VCardText>
              <div v-if="transactionsError" class="text-sm mb-3" style="color: #b91c1c;">
                {{ transactionsError }}
              </div>

              <DataTable
                :value="transactions"
                dataKey="id"
                class="p-datatable-sm"
                :loading="transactionsLoading"
              >
                <Column field="created_at" header="Дата" style="inline-size: 12ch;">
                  <template #body="{ data }">
                    {{ formatDateShort(data.created_at) }}
                  </template>
                </Column>

                <Column field="transaction_type.name" header="Тип" style="inline-size: 22ch;">
                  <template #body="{ data }">
                    {{ data.transaction_type?.name ?? '—' }}
                  </template>
                </Column>

                <Column field="sum" header="Сумма" style="inline-size: 18ch;">
                  <template #body="{ data }">
                    <span
                      :style="{
                        color: Number(data.transaction_type?.sign ?? 0) < 0 ? '#b91c1c' : '#065f46',
                        fontWeight: 600,
                      }"
                    >
                      {{ formatSignedSum(data) }}
                    </span>
                  </template>
                </Column>

                <Column field="cashbox.name" header="Касса" style="inline-size: 22ch;">
                  <template #body="{ data }">
                    <CashboxCell :cashbox="data.cashbox" size="sm" />
                  </template>
                </Column>

                <Column field="counterparty.name" header="Контрагент">
                  <template #body="{ data }">
                    {{ data.counterparty?.name ?? '—' }}
                  </template>
                </Column>

                <Column header="Оплата" style="inline-size: 10ch;">
                  <template #body="{ data }">
                    <div class="d-flex flex-column" style="line-height: 1.15;">
                      <span :class="statusLines(data.is_paid, data.date_is_paid)[0].className">
                        {{ statusLines(data.is_paid, data.date_is_paid)[0].text }}
                      </span>
                      <span class="text-medium-emphasis text-xs">
                        {{ statusLines(data.is_paid, data.date_is_paid)[1].text }}
                      </span>
                    </div>
                  </template>
                </Column>

                <Column header="Закрыта" style="inline-size: 10ch;">
                  <template #body="{ data }">
                    <div class="d-flex flex-column" style="line-height: 1.15;">
                      <span :class="statusLines(data.is_completed, data.date_is_completed)[0].className">
                        {{ statusLines(data.is_completed, data.date_is_completed)[0].text }}
                      </span>
                      <span class="text-medium-emphasis text-xs">
                        {{ statusLines(data.is_completed, data.date_is_completed)[1].text }}
                      </span>
                    </div>
                  </template>
                </Column>

                <template #empty>
                  <div class="text-center py-6 text-muted">Нет данных.</div>
                </template>
              </DataTable>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
