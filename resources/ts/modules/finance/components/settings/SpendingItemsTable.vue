<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Button from 'primevue/button'
import { $api } from '@/utils/api'
import { SPENDING_ITEM_COLUMNS } from '@/modules/finance/config/spendingItemsTable.config'
import { useDictionariesStore } from '@/stores/dictionaries'
import type { SpendingItem } from '@/types/finance'

type SpendingItemRow = SpendingItem & {
  fund_name?: string
  cashflow_name?: string
}

const props = defineProps<{
  rows: SpendingItem[]
  loading: boolean
  totalRecords: number
  scrollHeight: string
  virtualScrollerOptions: Record<string, any>
  filters: any
  canCreate?: boolean
  canEdit?: boolean
  canDelete?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:filters', value: any): void
  (e: 'sort', event: any): void
  (e: 'reset-filters'): void
  (e: 'reload'): void
}>()

const filtersModel = computed({
  get: () => props.filters,
  set: value => emit('update:filters', value),
})

const totalLabel = computed(() => Number(props.totalRecords ?? 0).toLocaleString('ru-RU'))
const canCreate = computed(() => props.canCreate !== false)
const canEdit = computed(() => props.canEdit !== false)
const canDelete = computed(() => props.canDelete !== false)

const dictionaries = useDictionariesStore()

const fundMap = computed(
  () => new Map(dictionaries.spendingFunds.map(item => [String(item.id), item.name])),
)

const cashflowMap = computed(() => {
  const list = dictionaries.cashflowItems
  const map = new Map<string, string>()
  for (const item of list) {
    const label = [item.code, item.name].filter(Boolean).join(' — ')
    map.set(String(item.id), label)
  }
  return map
})

const rows = computed<SpendingItemRow[]>(() =>
  (props.rows || []).map(row => ({
    ...row,
    fund_name: row.fond_id != null ? fundMap.value.get(String(row.fond_id)) ?? '' : '',
    cashflow_name:
      row.cashflow_item_id != null
        ? cashflowMap.value.get(String(row.cashflow_item_id)) ?? ''
        : '',
  })),
)

const cashflowOptions = computed(() =>
  dictionaries.cashflowItems
    .filter(item => String(item.direction ?? '') === 'OUT' && item.is_active !== false)
    .map(item => ({
      id: item.id,
      label: [item.code, item.name].filter(Boolean).join(' — '),
    })),
)

const dialogOpen = ref(false)
const saving = ref(false)
const deletingId = ref<number | null>(null)
const errorMessage = ref('')
const cashflowOverrides = reactive<Record<number, number | null>>({})
const inlineSaving = reactive<Record<number, boolean>>({})

const form = reactive({
  id: null as number | null,
  name: '',
  fond_id: null as number | null,
  cashflow_item_id: null as number | null,
  description: '',
  is_active: true,
})

const canSave = computed(() => (form.id ? canEdit.value : canCreate.value))
const isReadOnly = computed(() => !canSave.value)

const resetForm = () => {
  form.id = null
  form.name = ''
  form.fond_id = null
  form.cashflow_item_id = null
  form.description = ''
  form.is_active = true
  errorMessage.value = ''
}

const openCreate = async () => {
  if (!canCreate.value) return
  resetForm()
  await Promise.all([dictionaries.loadSpendingFunds(), dictionaries.loadCashflowItems()])
  dialogOpen.value = true
}

const openEdit = async (row: SpendingItemRow) => {
  if (!canEdit.value) return
  resetForm()
  form.id = row.id
  form.name = row.name ?? ''
  form.fond_id = row.fond_id ?? null
  form.cashflow_item_id = row.cashflow_item_id ?? null
  form.description = row.description ?? ''
  form.is_active = row.is_active ?? true
  await Promise.all([dictionaries.loadSpendingFunds(), dictionaries.loadCashflowItems()])
  dialogOpen.value = true
}

const getCashflowValue = (row: SpendingItemRow) => {
  if (!row?.id) return row?.cashflow_item_id ?? null
  if (Object.prototype.hasOwnProperty.call(cashflowOverrides, row.id)) {
    return cashflowOverrides[row.id] ?? null
  }
  return row.cashflow_item_id ?? null
}

const setInlineSaving = (rowId: number, value: boolean) => {
  inlineSaving[rowId] = value
}

const updateCashflow = async (row: SpendingItemRow, nextId: number | null) => {
  if (!canEdit.value) return
  if (!row?.id) return
  const currentValue = row.cashflow_item_id ?? null
  if (currentValue === nextId) return
  cashflowOverrides[row.id] = nextId ?? null
  setInlineSaving(row.id, true)
  errorMessage.value = ''
  try {
    await $api(`settings/spending-items/${row.id}`, {
      method: 'PATCH',
      body: { cashflow_item_id: nextId ?? null },
    })
    await dictionaries.loadSpendingItems(true)
    emit('reload')
    delete cashflowOverrides[row.id]
  } catch (error: any) {
    errorMessage.value =
      error?.data?.message ?? error?.response?.data?.message ?? 'Не удалось обновить статью ДДС.'
    cashflowOverrides[row.id] = currentValue
  } finally {
    setInlineSaving(row.id, false)
  }
}

const isInlineSaving = (row: SpendingItemRow) => (row?.id ? Boolean(inlineSaving[row.id]) : false)

const submit = async () => {
  if (!canSave.value) return
  if (!form.name.trim()) {
    errorMessage.value = 'Укажите название статьи.'
    return
  }
  if (!form.fond_id) {
    errorMessage.value = 'Выберите фонд.'
    return
  }

  saving.value = true
  errorMessage.value = ''
  try {
    const payload = {
      name: form.name.trim(),
      fond_id: form.fond_id,
      cashflow_item_id: form.cashflow_item_id ?? null,
      description: form.description?.trim() || null,
      is_active: form.is_active ? 1 : 0,
    }

    if (form.id) {
      await $api(`settings/spending-items/${form.id}`, {
        method: 'PATCH',
        body: payload,
      })
    } else {
      await $api('settings/spending-items', {
        method: 'POST',
        body: payload,
      })
    }

    dialogOpen.value = false
    await dictionaries.loadSpendingItems(true)
    emit('reload')
  } catch (error: any) {
    errorMessage.value =
      error?.data?.message ?? error?.response?.data?.message ?? 'Не удалось сохранить статью.'
  } finally {
    saving.value = false
  }
}

const removeItem = async (row: SpendingItemRow) => {
  if (!canDelete.value) return
  if (!window.confirm('Удалить статью расхода?')) return
  deletingId.value = row.id
  try {
    await $api(`settings/spending-items/${row.id}`, { method: 'DELETE' })
    await dictionaries.loadSpendingItems(true)
    emit('reload')
  } catch (error: any) {
    errorMessage.value =
      error?.data?.message ?? error?.response?.data?.message ?? 'Не удалось удалить статью.'
  } finally {
    deletingId.value = null
  }
}
</script>

<template>
  <div class="flex flex-column gap-3">
    <DataTable
      v-model:filters="filtersModel"
      :value="rows"
      filterDisplay="row"
      dataKey="id"
      class="p-datatable-sm"
      :loading="loading"
      :totalRecords="totalRecords"
      scrollable
      :scrollHeight="scrollHeight"
      :virtualScrollerOptions="virtualScrollerOptions"
      lazy
      stripedRows
      @sort="emit('sort', $event)"
    >
      <template #header>
        <div class="flex items-center justify-between gap-4">
          <TableTotalLabel label="Всего" :value="totalLabel" />
          <div class="flex items-center gap-2">
            <Button
              v-if="canCreate"
              label="Создать статью"
              icon="pi pi-plus"
              severity="success"
              @click="openCreate"
            />
            <Button
              label="Сброс фильтров"
              text
              @click="emit('reset-filters')"
            />
          </div>
        </div>
        <div v-if="errorMessage" class="text-sm mt-2" style="color: #b91c1c;">
          {{ errorMessage }}
        </div>
      </template>

      <Column
        :field="SPENDING_ITEM_COLUMNS.id.field"
        :header="SPENDING_ITEM_COLUMNS.id.header"
        :sortable="SPENDING_ITEM_COLUMNS.id.sortable"
        :showFilterMenu="false"
        :style="`inline-size: ${SPENDING_ITEM_COLUMNS.id.width};`"
        :headerStyle="`inline-size: ${SPENDING_ITEM_COLUMNS.id.width};`"
        :bodyStyle="`inline-size: ${SPENDING_ITEM_COLUMNS.id.width};`"
      />

      <Column
        :field="SPENDING_ITEM_COLUMNS.name.field"
        :header="SPENDING_ITEM_COLUMNS.name.header"
        :sortable="SPENDING_ITEM_COLUMNS.name.sortable"
        :showFilterMenu="false"
      >
        <template #filter="{ filterModel, filterCallback }">
          <InputText v-model="filterModel.value" class="w-full" @input="filterCallback()" />
        </template>
        <template #body="{ data }">
          {{ data.name ?? '' }}
        </template>
      </Column>

      <Column
        :field="SPENDING_ITEM_COLUMNS.fund.field"
        :header="SPENDING_ITEM_COLUMNS.fund.header"
        :showFilterMenu="false"
      >
        <template #filter="{ filterModel, filterCallback }">
          <Select
            v-model="filterModel.value"
            :options="dictionaries.spendingFunds"
            optionLabel="name"
            optionValue="id"
            class="w-full"
            @change="filterCallback()"
          />
        </template>
        <template #body="{ data }">
          {{ data.fund_name ?? '' }}
        </template>
      </Column>

      <Column
        :field="SPENDING_ITEM_COLUMNS.cashflow.field"
        :header="SPENDING_ITEM_COLUMNS.cashflow.header"
        :showFilterMenu="false"
      >
        <template #filter="{ filterModel, filterCallback }">
          <Select
            v-model="filterModel.value"
            :options="cashflowOptions"
            optionLabel="label"
            optionValue="id"
            class="w-full"
            @change="filterCallback()"
          />
        </template>
        <template #body="{ data }">
          <Select
            :modelValue="getCashflowValue(data)"
            :options="cashflowOptions"
            optionLabel="label"
            optionValue="id"
            class="w-full"
            :disabled="!canEdit || isInlineSaving(data)"
            @update:modelValue="updateCashflow(data, $event ?? null)"
          />
        </template>
      </Column>

      <Column
        :field="SPENDING_ITEM_COLUMNS.description.field"
        :header="SPENDING_ITEM_COLUMNS.description.header"
        :showFilterMenu="false"
      >
        <template #body="{ data }">
          {{ data.description ?? '' }}
        </template>
      </Column>

      <Column
        :field="SPENDING_ITEM_COLUMNS.isActive.field"
        :header="SPENDING_ITEM_COLUMNS.isActive.header"
        :showFilterMenu="false"
      >
        <template #body="{ data }">
          {{ data.is_active === true ? 'Да' : data.is_active === false ? 'Нет' : '' }}
        </template>
      </Column>

      <Column header="Действия" style="inline-size: 12ch;">
        <template #body="{ data }">
          <div class="flex items-center gap-2">
            <Button
              v-if="canEdit"
              icon="pi pi-pencil"
              text
              severity="info"
              @click="openEdit(data)"
            />
            <Button
              v-if="canDelete"
              icon="pi pi-trash"
              text
              severity="danger"
              :loading="deletingId === data.id"
              @click="removeItem(data)"
            />
          </div>
        </template>
      </Column>

      <template #empty>
        <div class="text-center py-6 text-muted">Нет данных.</div>
      </template>
    </DataTable>
  </div>

  <VDialog v-model="dialogOpen" max-width="520">
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <span>{{ form.id ? 'Редактировать статью' : 'Новая статья расхода' }}</span>
        <VBtn icon="tabler-x" variant="text" @click="dialogOpen = false" />
      </VCardTitle>
      <VCardText class="d-flex flex-column gap-4">
        <div v-if="errorMessage" class="text-sm text-error">{{ errorMessage }}</div>

        <VTextField v-model="form.name" label="Название" hide-details :disabled="isReadOnly" />
        <VSelect
          v-model="form.fond_id"
          :items="dictionaries.spendingFunds"
          item-title="name"
          item-value="id"
          label="Фонд"
          hide-details
          :disabled="isReadOnly"
        />
        <VSelect
          v-model="form.cashflow_item_id"
          :items="cashflowOptions"
          item-title="label"
          item-value="id"
          label="Статья ДДС"
          clearable
          hide-details
          :disabled="isReadOnly"
        />
        <VTextarea
          v-model="form.description"
          label="Описание"
          rows="2"
          auto-grow
          hide-details
          :disabled="isReadOnly"
        />
        <VSwitch v-model="form.is_active" label="Активна" inset :disabled="isReadOnly" />
      </VCardText>
      <VCardActions class="justify-end gap-2">
        <VBtn variant="text" @click="dialogOpen = false">Отмена</VBtn>
        <VBtn color="primary" :loading="saving" :disabled="isReadOnly" @click="submit">Сохранить</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
