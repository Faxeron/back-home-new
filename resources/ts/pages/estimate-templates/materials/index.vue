<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import { useTableInfinite } from '@/composables/useTableLazy'
import type { EstimateTemplateMaterial } from '@/types/estimate-templates'

const router = useRouter()
const tableRef = ref<any>(null)
const scrollHeight = ref('700px')
const search = ref('')

const params = computed(() => ({
  q: search.value || undefined,
}))

const {
  data,
  total: totalRecords,
  loading,
  reset,
  virtualScrollerOptions,
} = useTableInfinite<EstimateTemplateMaterial>({
  endpoint: 'estimate-templates/materials',
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

const handleCreate = () => {
  router.push({ path: '/estimate-templates/materials/new' })
}

const handleEdit = (row: EstimateTemplateMaterial) => {
  router.push({ path: `/estimate-templates/materials/${row.id}` })
}

const totalLabel = computed(() => Number(totalRecords.value ?? 0).toLocaleString('ru-RU'))

const formatDate = (value?: string | null) => {
  if (!value) return '—'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? '—' : date.toLocaleDateString('ru-RU')
}

onMounted(async () => {
  await reset()
  await nextTick()
  updateScrollHeight()
  window.addEventListener('resize', handleResize)
})

watch(search, () => {
  reset()
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
})
</script>

<template>
  <DataTable
    ref="tableRef"
    :value="data"
    dataKey="id"
    class="p-datatable-sm"
    :loading="loading"
    :totalRecords="totalRecords"
    scrollable
    :scrollHeight="scrollHeight"
    :virtualScrollerOptions="virtualScrollerOptions"
    lazy
    stripedRows
  >
    <template #header>
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <InputText
            v-model="search"
            class="w-64"
            placeholder="Поиск по названию"
          />
        </div>
        <div class="flex items-center gap-2">
          <span class="text-sm text-muted">Всего: {{ totalLabel }}</span>
          <Button
            label="Новый шаблон"
            icon="pi pi-plus"
            size="small"
            @click="handleCreate"
          />
        </div>
      </div>
    </template>

    <Column field="id" header="ID" style="inline-size: 6ch;" />
    <Column field="title" header="Название" />
    <Column
      field="items_count"
      header="Позиции"
      style="inline-size: 10ch;"
    >
      <template #body="{ data: row }">
        {{ Array.isArray(row.items) ? row.items.length : 0 }}
      </template>
    </Column>
    <Column field="updated_at" header="Обновлен" style="inline-size: 12ch;">
      <template #body="{ data: row }">
        {{ formatDate(row.updated_at) }}
      </template>
    </Column>
    <Column header="" style="inline-size: 6ch;">
      <template #body="{ data: row }">
        <Button
          icon="pi pi-pencil"
          text
          aria-label="Редактировать шаблон"
          @click="handleEdit(row)"
        />
      </template>
    </Column>

    <template #empty>
      <div class="text-center py-6 text-muted">Нет шаблонов.</div>
    </template>

    <template #loading>
      <div class="text-center py-6 text-muted">Загрузка...</div>
    </template>
  </DataTable>
</template>
