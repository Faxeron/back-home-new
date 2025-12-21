<script setup lang="ts">
import Column from 'primevue/column'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { useDictionariesStore } from '@/stores/dictionaries'
import { TRANSACTION_BADGES } from '@/constants/transactionBadges'

type ColumnType = 'text' | 'money' | 'date' | 'dict' | 'status' | 'transaction_type'

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
        {{ dict[col.dict || '']?.find((item: any) => item.id === data[col.field])?.name ?? '-' }}
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
