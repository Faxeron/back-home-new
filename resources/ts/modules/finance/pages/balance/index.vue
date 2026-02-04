<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { $api } from '@/utils/api'
import { formatSum } from '@/utils/formatters/finance'
import CashboxBadge from '@/components/cashboxes/CashboxBadge.vue'

type CashboxBalance = {
  id: number
  name?: string | null
  balance?: number | null
  logo_url?: string | null
}

const rows = ref<CashboxBalance[]>([])
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
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось загрузить баланс.'
    rows.value = []
  } finally {
    loading.value = false
  }
}

onMounted(loadBalances)
</script>

<template>
  <VCard>
    <VCardTitle class="d-flex align-center justify-space-between">
      <span>Баланс</span>
      <VBtn color="primary" variant="tonal" @click="loadBalances">Обновить</VBtn>
    </VCardTitle>
    <VCardText>
      <div v-if="errorMessage" class="text-sm mb-3" style="color: #b91c1c;">
        {{ errorMessage }}
      </div>
      <DataTable
        :value="rows"
        dataKey="id"
        class="p-datatable-sm"
        :loading="loading"
      >
        <Column field="name" header="Касса">
          <template #body="{ data }">
            <CashboxBadge :cashbox="data" size="sm" />
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
        Итог: {{ formatSum(totalBalance) }}
      </div>
    </VCardText>
  </VCard>
</template>
