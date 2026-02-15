<script setup lang="ts">
import { computed } from 'vue'
import Column from 'primevue/column'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { useDictionariesStore } from '@/stores/dictionaries'
import { TRANSACTION_BADGES } from '@/constants/transactionBadges'

type ColumnType = 'text' | 'money' | 'date' | 'dict' | 'status' | 'transaction_type'
type DictItem = { id: number | string; name?: string | null }
type DictKey =
  | 'cashBoxes'
  | 'companies'
  | 'spendingFunds'
  | 'spendingItems'
  | 'cashflowItems'
  | 'saleTypes'
  | 'transactionTypes'
  | 'paymentMethods'
  | 'financeObjects'
  | 'counterparties'
  | 'cities'
  | 'contractStatuses'

const props = defineProps<{
  columns: Array<{
    field: string
    header: string
    type?: ColumnType
    dict?: string
    width?: number
  }>
}>()

const dict = useDictionariesStore()
const dictCollections = computed<Record<DictKey, DictItem[]>>(() => ({
  cashBoxes: dict.cashBoxes,
  companies: dict.companies,
  spendingFunds: dict.spendingFunds,
  spendingItems: dict.spendingItems,
  cashflowItems: dict.cashflowItems,
  saleTypes: dict.saleTypes,
  transactionTypes: dict.transactionTypes,
  paymentMethods: dict.paymentMethods,
  financeObjects: dict.financeObjects,
  counterparties: dict.counterparties,
  cities: dict.cities,
  contractStatuses: dict.contractStatuses,
}))

const dictValueName = (dictKey: string | undefined, value: unknown) => {
  if (!dictKey) return '-'
  const list = (dictCollections.value as Record<string, DictItem[]>)[dictKey] ?? []
  const item = list.find(entry => String(entry.id) === String(value))
  return item?.name ?? '-'
}

const formatDate = (value: string | null) => {
  if (!value) return '-'
  const date = new Date(value)
  return date.toLocaleDateString('ru-RU')
}
</script>

<template>
  <template v-for="col in columns" :key="col.field">
    <Column
      v-if="col.type === 'money'"
      :field="col.field"
      :header="col.header"
      :style="{ width: (col.width || 140) + 'px' }"
    >
      <template #body="{ data }">
        <span>{{ Number(data[col.field]).toLocaleString('ru-RU') }} ₽</span>
      </template>
    </Column>

    <Column
      v-else-if="col.type === 'date'"
      :field="col.field"
      :header="col.header"
      :style="{ width: (col.width || 150) + 'px' }"
    >
      <template #body="{ data }">
        {{ formatDate(data[col.field]) }}
      </template>
    </Column>

    <Column
      v-else-if="col.type === 'dict'"
      :field="col.field"
      :header="col.header"
      :style="{ width: (col.width || 200) + 'px' }"
    >
      <template #body="{ data }">
        {{ dictValueName(col.dict, data[col.field]) }}
      </template>
    </Column>

    <Column
      v-else-if="col.type === 'status'"
      :field="col.field"
      :header="col.header"
      :style="{ width: (col.width || 130) + 'px' }"
    >
      <template #body="{ data }">
        <StatusBadge :status="data[col.field]" />
      </template>
    </Column>

    <Column
      v-else-if="col.type === 'transaction_type'"
      :field="col.field"
      :header="col.header"
      :style="{ width: (col.width || 150) + 'px' }"
    >
      <template #body="{ data }">
        <StatusBadge :status="data.transaction_type?.code" :map="TRANSACTION_BADGES" />
      </template>
    </Column>

    <Column
      v-else
      :field="col.field"
      :header="col.header"
      :style="{ width: (col.width || 150) + 'px' }"
    />
  </template>
</template>
