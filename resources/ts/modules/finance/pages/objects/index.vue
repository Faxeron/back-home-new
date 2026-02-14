<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useTableInfinite } from '@/composables/useTableLazy'
import { createFinanceObject } from '@/modules/finance/api/finance-objects.api'
import type { FinanceObject, FinanceObjectStatus, FinanceObjectType } from '@/types/finance'
import { useDictionariesStore } from '@/stores/dictionaries'
import { formatDateShort } from '@/utils/formatters/finance'

type FinanceObjectRow = FinanceObject

const router = useRouter()
const dictionaries = useDictionariesStore()

const filters = reactive({
  q: '',
  type: null as FinanceObjectType | null,
  status: null as FinanceObjectStatus | null,
})

const serverParams = computed(() => {
  const params: Record<string, any> = {}
  if (filters.q.trim()) params.q = filters.q.trim()
  if (filters.type) params.type = filters.type
  if (filters.status) params.status = filters.status
  return params
})

const {
  data,
  total,
  loading,
  hasMore,
  loadMore,
  reset,
} = useTableInfinite<FinanceObjectRow>({
  endpoint: 'finance/objects',
  perPage: 100,
  rowHeight: 52,
  params: () => serverParams.value,
})

const typeOptions: Array<{ label: string; value: FinanceObjectType }> = [
  { label: 'CONTRACT', value: 'CONTRACT' },
  { label: 'PROJECT', value: 'PROJECT' },
  { label: 'EVENT', value: 'EVENT' },
  { label: 'ORDER', value: 'ORDER' },
  { label: 'SUBSCRIPTION', value: 'SUBSCRIPTION' },
  { label: 'TENDER', value: 'TENDER' },
  { label: 'SERVICE', value: 'SERVICE' },
  { label: 'INTERNAL', value: 'INTERNAL' },
]

const statusOptions: Array<{ label: string; value: FinanceObjectStatus }> = [
  { label: 'DRAFT', value: 'DRAFT' },
  { label: 'ACTIVE', value: 'ACTIVE' },
  { label: 'ON_HOLD', value: 'ON_HOLD' },
  { label: 'DONE', value: 'DONE' },
  { label: 'CANCELED', value: 'CANCELED' },
  { label: 'ARCHIVED', value: 'ARCHIVED' },
]

const createOpen = ref(false)
const createSaving = ref(false)
const createError = ref('')

const createForm = reactive({
  type: 'PROJECT' as FinanceObjectType,
  name: '',
  code: '',
  status: 'DRAFT' as FinanceObjectStatus,
  date_from: new Date().toISOString().slice(0, 10),
  date_to: '',
  counterparty_id: null as number | null,
  description: '',
})

const resetCreateForm = () => {
  createForm.type = 'PROJECT'
  createForm.name = ''
  createForm.code = ''
  createForm.status = 'DRAFT'
  createForm.date_from = new Date().toISOString().slice(0, 10)
  createForm.date_to = ''
  createForm.counterparty_id = null
  createForm.description = ''
  createError.value = ''
}

const openCreateDialog = async () => {
  resetCreateForm()
  await dictionaries.loadCounterparties()
  createOpen.value = true
}

const submitCreate = async () => {
  createSaving.value = true
  createError.value = ''
  try {
    await createFinanceObject({
      type: createForm.type,
      name: createForm.name,
      code: createForm.code || null,
      status: createForm.status,
      date_from: createForm.date_from,
      date_to: createForm.date_to || null,
      counterparty_id: createForm.counterparty_id,
      description: createForm.description || null,
    })
    createOpen.value = false
    await reset()
  } catch (error: any) {
    createError.value =
      error?.data?.message ??
      error?.response?.data?.message ??
      'Failed to create finance object.'
  } finally {
    createSaving.value = false
  }
}

const openDetails = (item: FinanceObjectRow) => {
  router.push({ path: `/operations/finance-objects/${item.id}` })
}

let reloadTimer: number | undefined
watch(
  () => ({ ...filters }),
  () => {
    if (reloadTimer) window.clearTimeout(reloadTimer)
    reloadTimer = window.setTimeout(() => reset(), 250)
  },
  { deep: true },
)

onMounted(async () => {
  await dictionaries.loadCounterparties()
  await reset()
})
</script>

<template>
  <VCard>
    <VCardTitle class="d-flex align-center justify-space-between gap-3">
      <span>Finance Objects</span>
      <VBtn color="primary" @click="openCreateDialog">New Object</VBtn>
    </VCardTitle>
    <VCardText class="d-flex flex-column gap-3">
      <div class="d-flex flex-wrap gap-3">
        <VTextField v-model="filters.q" label="Search" density="comfortable" hide-details />
        <VSelect
          v-model="filters.type"
          :items="typeOptions"
          item-title="label"
          item-value="value"
          label="Type"
          clearable
          density="comfortable"
          hide-details
        />
        <VSelect
          v-model="filters.status"
          :items="statusOptions"
          item-title="label"
          item-value="value"
          label="Status"
          clearable
          density="comfortable"
          hide-details
        />
        <VBtn variant="text" @click="() => { filters.q = ''; filters.type = null; filters.status = null }">Reset</VBtn>
      </div>

      <div class="text-body-2 text-medium-emphasis">
        Total: {{ Number(total ?? 0).toLocaleString('ru-RU') }}
      </div>

      <VProgressLinear v-if="loading" indeterminate color="primary" />

      <VTable density="compact">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Type</th>
            <th>Status</th>
            <th>Code</th>
            <th>Counterparty</th>
            <th>Date From</th>
            <th>Date To</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in data"
            :key="item.id"
            class="cursor-pointer"
            @click="openDetails(item)"
          >
            <td>{{ item.id }}</td>
            <td>{{ item.name }}</td>
            <td>{{ item.type }}</td>
            <td>{{ item.status }}</td>
            <td>{{ item.code ?? '' }}</td>
            <td>{{ item.counterparty?.name ?? '' }}</td>
            <td>{{ formatDateShort(item.date_from) }}</td>
            <td>{{ formatDateShort(item.date_to ?? null) }}</td>
          </tr>
          <tr v-if="!data.length && !loading">
            <td colspan="8" class="text-center py-4 text-medium-emphasis">No data</td>
          </tr>
        </tbody>
      </VTable>

      <div class="d-flex justify-center py-2">
        <VBtn v-if="hasMore" :loading="loading" variant="text" @click="loadMore">Load More</VBtn>
      </div>
    </VCardText>
  </VCard>

  <VDialog v-model="createOpen" max-width="760">
    <VCard>
      <VCardTitle>Create Finance Object</VCardTitle>
      <VCardText class="d-flex flex-column gap-3">
        <div v-if="createError" class="text-error text-sm">{{ createError }}</div>

        <div class="d-flex flex-wrap gap-3">
          <VSelect
            v-model="createForm.type"
            :items="typeOptions"
            item-title="label"
            item-value="value"
            label="Type"
            hide-details
          />
          <VSelect
            v-model="createForm.status"
            :items="statusOptions"
            item-title="label"
            item-value="value"
            label="Status"
            hide-details
          />
        </div>

        <VTextField v-model="createForm.name" label="Name" hide-details />
        <VTextField v-model="createForm.code" label="Code" hide-details />

        <div class="d-flex flex-wrap gap-3">
          <VTextField v-model="createForm.date_from" label="Date From" type="date" hide-details />
          <VTextField v-model="createForm.date_to" label="Date To" type="date" hide-details />
        </div>

        <VSelect
          v-model="createForm.counterparty_id"
          :items="dictionaries.counterparties"
          item-title="name"
          item-value="id"
          label="Counterparty"
          clearable
          hide-details
        />

        <VTextarea v-model="createForm.description" label="Description" rows="3" auto-grow hide-details />
      </VCardText>
      <VCardActions class="justify-end gap-2">
        <VBtn variant="text" @click="createOpen = false">Cancel</VBtn>
        <VBtn color="primary" :loading="createSaving" @click="submitCreate">Create</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

