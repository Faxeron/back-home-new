<script setup lang="ts">
import { onMounted, reactive } from 'vue'
import BaseDataTable from '@/components/BaseDataTable.vue'
import { useTableLazy } from '@/composables/useTableLazy'
import { tableConfigTransactions } from '@/config/tables/transactions'
import { useDictionariesStore } from '@/stores/dictionaries'

const dictionaries = useDictionariesStore()

const BOOLEAN_OPTIONS = [
  { label: 'All', value: null },
  { label: 'Yes', value: true },
  { label: 'No', value: false },
]

// Clone columns to avoid mutating the shared config.
const config = reactive({
  ...tableConfigTransactions,
  columns: tableConfigTransactions.columns.map(col => ({ ...col })),
})

const applyDictionaryOptions = () => {
  config.columns = tableConfigTransactions.columns.map(col => {
    if (col.field === 'cashbox_id') {
      return {
        ...col,
        options: (dictionaries.cashBoxes || []).map(item => ({
          label: item.name,
          value: item.id,
        })),
      }
    }
    if (col.field === 'transaction_type_id') {
      return {
        ...col,
        options: (dictionaries.transactionTypes || []).map(item => ({
          label: item.name ?? item.code ?? item.id,
          value: item.id,
        })),
      }
    }
    if (col.field === 'is_paid') {
      return { ...col, options: BOOLEAN_OPTIONS }
    }
    return { ...col }
  })
}

const { data, total, loading, load } = useTableLazy(config)

onMounted(async () => {
  await Promise.all([dictionaries.loadCashBoxes(), dictionaries.loadTransactionTypes()])
  applyDictionaryOptions()
  await load()
})
</script>

<template>
  <BaseDataTable
    :config="config"
    :data="data"
    :total="total"
    :loading="loading"
    :load="load"
  />
</template>
