<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import EstimatesTable from '@/components/tables/estimates/EstimatesTable.vue'
import { useTableInfinite } from '@/composables/useTableLazy'
import type { Estimate } from '@/types/estimates'

const router = useRouter()
const tableRef = ref<any>(null)
const scrollHeight = ref('700px')

const search = ref('')
const dateRange = ref<(Date | null)[]>([])

const params = computed(() => {
  const [from, to] = dateRange.value ?? []
  const format = (value: Date | null) =>
    value ? value.toISOString().slice(0, 10) : null

  return {
    q: search.value || undefined,
    date_from: format(from),
    date_to: format(to),
  }
})

const {
  data,
  total: totalRecords,
  loading,
  reset,
  virtualScrollerOptions,
} = useTableInfinite<Estimate>({
  endpoint: 'estimates',
  params: () => params.value,
  perPage: 50,
  rowHeight: 52,
})

const updateScrollHeight = () => {
  const tableEl = tableRef.value?.$el as HTMLElement | undefined
  if (!tableEl) return
  const rect = tableEl.getBoundingClientRect()
  const padding = 24
  const nextHeight = Math.max(320, window.innerHeight - rect.top - padding)
  scrollHeight.value = `${Math.floor(nextHeight)}px`
}

const handleResize = () => {
  updateScrollHeight()
}

const handleReset = () => {
  search.value = ''
  dateRange.value = []
  reset()
}

const handleOpen = (row: Estimate) => {
  router.push({ path: `/estimates/${row.id}/edit` })
}

const handleCreate = () => {
  router.push({ path: '/estimates/new' })
}

onMounted(async () => {
  await reset()
  await nextTick()
  updateScrollHeight()
  window.addEventListener('resize', handleResize)
})

watch([search, dateRange], () => {
  reset()
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
})
</script>

<template>
  <EstimatesTable
    ref="tableRef"
    v-model:search="search"
    v-model:dateRange="dateRange"
    :rows="data"
    :loading="loading"
    :totalRecords="totalRecords"
    :scrollHeight="scrollHeight"
    :virtualScrollerOptions="virtualScrollerOptions"
    @reset="handleReset"
    @open="handleOpen"
    @create="handleCreate"
  />
</template>
