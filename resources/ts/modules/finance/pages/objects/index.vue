<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useTableInfinite } from '@/composables/useTableLazy'
import { createFinanceObject, listFinanceObjectTypes } from '@/modules/finance/api/finance-objects.api'
import type { FinanceObject, FinanceObjectStatus, FinanceObjectType, FinanceObjectTypeView } from '@/types/finance'
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

const typeCatalog = ref<FinanceObjectTypeView[]>([])
const typeNameMap = computed(() => {
  const map = new Map<FinanceObjectType, string>()
  typeCatalog.value.forEach(item => map.set(item.key, item.name))
  return map
})

const typeOptions = computed(() =>
  typeCatalog.value.map(item => ({
    label: item.is_enabled ? item.name : `${item.name} (отключен)`,
    value: item.key,
  })),
)

const createTypeOptions = computed(() =>
  typeCatalog.value
    .filter(item => item.is_enabled)
    .map(item => ({
      label: item.name,
      value: item.key,
    })),
)
const hasEnabledTypes = computed(() => createTypeOptions.value.length > 0)

const statusOptions: Array<{ label: string; value: FinanceObjectStatus }> = [
  { label: 'Черновик', value: 'DRAFT' },
  { label: 'Активный', value: 'ACTIVE' },
  { label: 'На паузе', value: 'ON_HOLD' },
  { label: 'Завершен', value: 'DONE' },
  { label: 'Отменен', value: 'CANCELED' },
  { label: 'Архив', value: 'ARCHIVED' },
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

const loadTypeCatalog = async () => {
  try {
    const response: any = await listFinanceObjectTypes({ include_disabled: 1 })
    const rows = Array.isArray(response?.data) ? response.data : Array.isArray(response) ? response : []
    typeCatalog.value = rows
  } catch {
    typeCatalog.value = []
  }
}

const resetCreateForm = () => {
  createForm.type = createTypeOptions.value[0]?.value ?? 'PROJECT'
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
  if (!hasEnabledTypes.value) {
    createError.value = 'Для текущей компании нет включенных типов объектов учета.'
    return
  }
  resetCreateForm()
  if (!createTypeOptions.value.length) {
    await loadTypeCatalog()
    resetCreateForm()
  }
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
      'Не удалось создать объект учета.'
  } finally {
    createSaving.value = false
  }
}

const openDetails = (item: FinanceObjectRow) => {
  router.push({ path: `/operations/finance-objects/${item.id}` })
}

const resolveTypeName = (type: FinanceObjectType) => typeNameMap.value.get(type) ?? type
const statusNameMap: Record<FinanceObjectStatus, string> = {
  DRAFT: 'Черновик',
  ACTIVE: 'Активный',
  ON_HOLD: 'На паузе',
  DONE: 'Завершен',
  CANCELED: 'Отменен',
  ARCHIVED: 'Архив',
}
const resolveStatusName = (item: FinanceObjectRow) => item.status_name_ru ?? statusNameMap[item.status] ?? item.status

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
  await Promise.all([
    dictionaries.loadCounterparties(),
    loadTypeCatalog(),
    reset(),
  ])
})
</script>

<template>
  <VCard>
    <VCardTitle class="d-flex align-center justify-space-between gap-3">
      <span>Объекты учета</span>
      <VBtn color="primary" :disabled="!hasEnabledTypes" @click="openCreateDialog">Новый объект</VBtn>
    </VCardTitle>
    <VCardText class="d-flex flex-column gap-3">
      <div class="d-flex flex-wrap gap-3">
        <VTextField v-model="filters.q" label="Поиск" density="comfortable" hide-details />
        <VSelect
          v-model="filters.type"
          :items="typeOptions"
          item-title="label"
          item-value="value"
          label="Тип"
          clearable
          density="comfortable"
          hide-details
        />
        <VSelect
          v-model="filters.status"
          :items="statusOptions"
          item-title="label"
          item-value="value"
          label="Статус"
          clearable
          density="comfortable"
          hide-details
        />
        <VBtn variant="text" @click="() => { filters.q = ''; filters.type = null; filters.status = null }">Сбросить</VBtn>
      </div>

      <div class="text-body-2 text-medium-emphasis">
        Всего: {{ Number(total ?? 0).toLocaleString('ru-RU') }}
      </div>

      <VProgressLinear v-if="loading" indeterminate color="primary" />

      <VTable density="compact">
        <thead>
          <tr>
            <th>ID</th>
            <th>Название</th>
            <th>Тип</th>
            <th>Статус</th>
            <th>Код</th>
            <th>Контрагент</th>
            <th>Дата начала</th>
            <th>Дата окончания</th>
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
            <td>{{ resolveTypeName(item.type) }}</td>
            <td>{{ resolveStatusName(item) }}</td>
            <td>{{ item.code ?? '' }}</td>
            <td>{{ item.counterparty?.name ?? '' }}</td>
            <td>{{ formatDateShort(item.date_from) }}</td>
            <td>{{ formatDateShort(item.date_to ?? null) }}</td>
          </tr>
          <tr v-if="!data.length && !loading">
            <td colspan="8" class="text-center py-4 text-medium-emphasis">Нет данных</td>
          </tr>
        </tbody>
      </VTable>

      <div class="d-flex justify-center py-2">
        <VBtn v-if="hasMore" :loading="loading" variant="text" @click="loadMore">Показать еще</VBtn>
      </div>
    </VCardText>
  </VCard>

  <VDialog v-model="createOpen" max-width="760">
    <VCard>
      <VCardTitle>Создать объект учета</VCardTitle>
      <VCardText class="d-flex flex-column gap-3">
        <div v-if="createError" class="text-error text-sm">{{ createError }}</div>

        <div class="d-flex flex-wrap gap-3">
          <VSelect
            v-model="createForm.type"
            :items="createTypeOptions"
            item-title="label"
            item-value="value"
            label="Тип"
            hide-details
          />
          <VSelect
            v-model="createForm.status"
            :items="statusOptions"
            item-title="label"
            item-value="value"
            label="Статус"
            hide-details
          />
        </div>

        <VTextField v-model="createForm.name" label="Название" hide-details />
        <VTextField v-model="createForm.code" label="Код" hide-details />

        <div class="d-flex flex-wrap gap-3">
          <VTextField v-model="createForm.date_from" label="Дата начала" type="date" hide-details />
          <VTextField v-model="createForm.date_to" label="Дата окончания" type="date" hide-details />
        </div>

        <VSelect
          v-model="createForm.counterparty_id"
          :items="dictionaries.counterparties"
          item-title="name"
          item-value="id"
          label="Контрагент"
          clearable
          hide-details
        />

        <VTextarea v-model="createForm.description" label="Описание" rows="3" auto-grow hide-details />
      </VCardText>
      <VCardActions class="justify-end gap-2">
        <VBtn variant="text" @click="createOpen = false">Отмена</VBtn>
        <VBtn color="primary" :loading="createSaving" @click="submitCreate">Создать</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
