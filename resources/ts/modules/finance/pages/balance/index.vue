<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { $api } from '@/utils/api'
import NewCashboxesBalance, { type CashboxBalanceRow } from '@/views/dashboards/new/NewCashboxesBalance.vue'

const rows = ref<CashboxBalanceRow[]>([])
const loading = ref(false)
const errorMessage = ref('')

const totalBalance = computed(() =>
  rows.value.reduce((sum, row) => sum + (Number(row.balance ?? 0) || 0), 0),
)

const loadBalances = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const response: any = await $api('finance/cashboxes')
    rows.value = response?.data ?? []
  }
  catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось загрузить баланс касс.'
    rows.value = []
  }
  finally {
    loading.value = false
  }
}

onMounted(loadBalances)
</script>

<template>
  <NewCashboxesBalance
    :rows="rows"
    :total="totalBalance"
    :loading="loading"
    :error="errorMessage"
    @refresh="loadBalances"
  />
</template>
