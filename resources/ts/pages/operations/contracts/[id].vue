<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Divider from 'primevue/divider'
import { $api } from '@/utils/api'
import { formatSum } from '@/utils/formatters/finance'
import type { Contract, ContractStatusChange } from '@/types/finance'

const route = useRoute()
const router = useRouter()

const contractId = computed(() => {
  const raw = route.params.id
  return Array.isArray(raw) ? raw[0] : raw
})

const contract = ref<Contract | null>(null)
const history = ref<ContractStatusChange[]>([])
const loading = ref(false)
const historyLoading = ref(false)
const errorMessage = ref('')
const historyError = ref('')

const formatMoney = (value?: number | null) => {
  if (value === null || value === undefined) return '—'
  return formatSum(value)
}

const statusColor = computed(() => contract.value?.status?.color ?? '#94a3b8')

const details = computed(() => [
  { label: 'Контрагент', value: contract.value?.counterparty?.name ?? '—' },
  { label: 'Адрес', value: contract.value?.address ?? '—' },
  { label: 'Сумма', value: formatMoney(contract.value?.total_amount ?? null) },
  { label: 'Оплачено', value: formatMoney(contract.value?.paid_amount ?? null) },
  { label: 'Долг', value: formatMoney(contract.value?.debt ?? null) },
  { label: 'Старт работ', value: contract.value?.work_start_date ?? '—' },
  { label: 'Окончание работ', value: contract.value?.work_end_date ?? '—' },
  { label: 'Тип продажи', value: contract.value?.sale_type?.name ?? '—' },
  { label: 'Менеджер', value: contract.value?.manager?.name ?? '—' },
  { label: 'Замерщик', value: contract.value?.measurer?.name ?? '—' },
])

const loadContract = async () => {
  if (!contractId.value) return
  loading.value = true
  errorMessage.value = ''
  try {
    const response: any = await $api(`contracts/${contractId.value}`)
    contract.value = response?.data ?? null
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось загрузить договор.'
  } finally {
    loading.value = false
  }
}

const loadHistory = async () => {
  if (!contractId.value) return
  historyLoading.value = true
  historyError.value = ''
  try {
    const response: any = await $api('contracts/status-history', {
      query: { contract_id: contractId.value, per_page: 200 },
    })
    history.value = response?.data ?? []
  } catch (error: any) {
    historyError.value = error?.response?.data?.message ?? 'Не удалось загрузить историю статусов.'
  } finally {
    historyLoading.value = false
  }
}

const goBack = () => router.push({ path: '/operations/contracts' })

watch(contractId, async () => {
  await loadContract()
  await loadHistory()
})

onMounted(async () => {
  await loadContract()
  await loadHistory()
})
</script>

<template>
  <div class="flex flex-column gap-4">
    <div class="flex flex-wrap align-items-center justify-between gap-3">
      <div class="flex flex-column gap-1">
        <div class="flex flex-wrap align-items-center gap-2">
          <h2 class="text-2xl font-semibold m-0">Договор #{{ contractId }}</h2>
          <Tag
            v-if="contract?.status?.name"
            :value="contract.status.name"
            :style="{ backgroundColor: statusColor, color: '#fff' }"
          />
        </div>
        <div class="text-sm text-muted">
          {{ contract?.title ?? '—' }}
        </div>
      </div>
      <Button
        label="Назад"
        icon="pi pi-arrow-left"
        outlined
        @click="goBack"
      />
    </div>

    <div v-if="errorMessage" class="p-3 border-round" style="background: #fee2e2; color: #b91c1c;">
      {{ errorMessage }}
    </div>

    <Card>
      <template #title>Данные договора</template>
      <template #content>
        <div v-if="loading" class="text-sm text-muted">Загрузка...</div>
        <DataTable
          v-else
          :value="details"
          dataKey="label"
          class="p-datatable-sm"
        >
          <Column field="label" header="Поле" />
          <Column field="value" header="Значение" />
        </DataTable>
      </template>
    </Card>

    <Card>
      <template #title>История статусов</template>
      <template #content>
        <div v-if="historyError" class="text-sm" style="color: #b91c1c;">
          {{ historyError }}
        </div>
        <div v-if="historyLoading" class="text-sm text-muted">Загрузка...</div>
        <DataTable
          v-else
          :value="history"
          dataKey="id"
          class="p-datatable-sm"
        >
          <Column field="changed_at" header="Дата" style="inline-size: 16ch;">
            <template #body="{ data: row }">
              {{ row.changed_at?.slice(0, 19).replace('T', ' ') ?? '—' }}
            </template>
          </Column>
          <Column field="previous_status" header="Было">
            <template #body="{ data: row }">
              {{ row.previous_status?.name ?? '—' }}
            </template>
          </Column>
          <Column field="new_status" header="Стало">
            <template #body="{ data: row }">
              {{ row.new_status?.name ?? '—' }}
            </template>
          </Column>
          <Column field="changed_by" header="Кем">
            <template #body="{ data: row }">
              {{ row.changed_by?.name ?? row.changed_by?.email ?? '—' }}
            </template>
          </Column>
          <template #empty>
            <div class="text-center py-6 text-muted">История пуста.</div>
          </template>
        </DataTable>
        <Divider />
        <div class="flex justify-end text-sm text-muted">
          История
        </div>
      </template>
    </Card>
  </div>
</template>
