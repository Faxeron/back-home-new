<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Button from 'primevue/button'
import { $api } from '@/utils/api'
import { useDictionariesStore } from '@/stores/dictionaries'
import { CASHFLOW_ITEM_COLUMNS } from '@/modules/finance/config/cashflowItemsTable.config'
import type { CashflowItem } from '@/types/finance'

type CashflowItemRow = CashflowItem & {
  parent_name?: string
}

const SECTION_OPTIONS = [
  { label: 'Операционная', value: 'OPERATING' },
  { label: 'Инвестиционная', value: 'INVESTING' },
  { label: 'Финансовая', value: 'FINANCING' },
]

const DIRECTION_OPTIONS = [
  { label: 'Поступления', value: 'IN' },
  { label: 'Расходы', value: 'OUT' },
]

const ACTIVE_OPTIONS = [
  { label: 'Активна', value: 1 },
  { label: 'Не активна', value: 0 },
]

const props = defineProps<{
  rows: CashflowItem[]
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

const dialogOpen = ref(false)
const saving = ref(false)
const deletingId = ref<number | null>(null)
const errorMessage = ref('')
const confirmDeleteOpen = ref(false)
const pendingDelete = ref<CashflowItemRow | null>(null)

const form = reactive({
  id: null as number | null,
  parent_id: null as number | null,
  code: '',
  name: '',
  section: 'OPERATING',
  direction: 'IN',
  sort_order: 100,
  is_active: true,
})

const cashflowOptions = computed(() => {
  const source = dictionaries.cashflowItems.length ? dictionaries.cashflowItems : props.rows
  return source.map(item => ({
    id: item.id,
    label: [item.code, item.name].filter(Boolean).join(' — '),
  }))
})

const parentOptions = computed(() =>
  cashflowOptions.value.filter(option => option.id !== form.id),
)

const parentMap = computed(() => new Map(cashflowOptions.value.map(item => [String(item.id), item.label])))

const rows = computed<CashflowItemRow[]>(() =>
  (props.rows || []).map(row => ({
    ...row,
    parent_name: row.parent_id != null ? parentMap.value.get(String(row.parent_id)) ?? '' : '',
  })),
)

const formatBool = (value?: boolean | null) => (value === true ? 'Да' : value === false ? 'Нет' : '')
const formatSection = (value?: string | null) =>
  SECTION_OPTIONS.find(option => option.value === value)?.label ?? value ?? ''
const formatDirection = (value?: string | null) =>
  DIRECTION_OPTIONS.find(option => option.value === value)?.label ?? value ?? ''

const canSave = computed(() => (form.id ? canEdit.value : canCreate.value))
const isReadOnly = computed(() => !canSave.value)

const resetForm = () => {
  form.id = null
  form.parent_id = null
  form.code = ''
  form.name = ''
  form.section = 'OPERATING'
  form.direction = 'IN'
  form.sort_order = 100
  form.is_active = true
  errorMessage.value = ''
}

const openCreate = () => {
  if (!canCreate.value) return
  resetForm()
  dialogOpen.value = true
}

const openEdit = (row: CashflowItemRow) => {
  if (!canEdit.value) return
  resetForm()
  form.id = row.id
  form.parent_id = row.parent_id ?? null
  form.code = row.code ?? ''
  form.name = row.name ?? ''
  form.section = row.section ?? 'OPERATING'
  form.direction = row.direction ?? 'IN'
  form.sort_order = row.sort_order ?? 100
  form.is_active = row.is_active ?? true
  dialogOpen.value = true
}

const submit = async () => {
  if (!canSave.value) return
  if (!form.code.trim() || !form.name.trim()) {
    errorMessage.value = 'Заполните код и название.'
    return
  }

  saving.value = true
  errorMessage.value = ''
  try {
    const payload = {
      parent_id: form.parent_id ?? null,
      code: form.code.trim(),
      name: form.name.trim(),
      section: form.section,
      direction: form.direction,
      sort_order: form.sort_order ?? 100,
      is_active: form.is_active ? 1 : 0,
    }

    if (form.id) {
      await $api(`cashflow-items/${form.id}`, {
        method: 'PUT',
        body: payload,
      })
    } else {
      await $api('cashflow-items', {
        method: 'POST',
        body: payload,
      })
    }

    dialogOpen.value = false
    await dictionaries.loadCashflowItems(true)
    emit('reload')
  } catch (error: any) {
    errorMessage.value =
      error?.data?.message ?? error?.response?.data?.message ?? 'Не удалось сохранить статью.'
  } finally {
    saving.value = false
  }
}

const requestRemoveItem = (row: CashflowItemRow) => {
  if (!canDelete.value) return
  pendingDelete.value = row
  confirmDeleteOpen.value = true
}

const removeItem = async () => {
  const row = pendingDelete.value
  if (!row) return
  deletingId.value = row.id
  try {
    await $api(`cashflow-items/${row.id}`, { method: 'DELETE' })
    await dictionaries.loadCashflowItems(true)
    emit('reload')
  } catch (error: any) {
    errorMessage.value =
      error?.data?.message ?? error?.response?.data?.message ?? 'Не удалось удалить статью.'
  } finally {
    deletingId.value = null
    confirmDeleteOpen.value = false
    pendingDelete.value = null
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
        :field="CASHFLOW_ITEM_COLUMNS.id.field"
        :header="CASHFLOW_ITEM_COLUMNS.id.header"
        :sortable="CASHFLOW_ITEM_COLUMNS.id.sortable"
        :showFilterMenu="false"
        :style="`inline-size: ${CASHFLOW_ITEM_COLUMNS.id.width};`"
        :headerStyle="`inline-size: ${CASHFLOW_ITEM_COLUMNS.id.width};`"
        :bodyStyle="`inline-size: ${CASHFLOW_ITEM_COLUMNS.id.width};`"
      />

      <Column
        :field="CASHFLOW_ITEM_COLUMNS.code.field"
        :header="CASHFLOW_ITEM_COLUMNS.code.header"
        :sortable="CASHFLOW_ITEM_COLUMNS.code.sortable"
      >
        <template #filter="{ filterModel, filterCallback }">
          <InputText v-model="filterModel.value" class="w-full" @input="filterCallback()" />
        </template>
        <template #body="{ data }">
          {{ data.code ?? '' }}
        </template>
      </Column>

      <Column
        :field="CASHFLOW_ITEM_COLUMNS.name.field"
        :header="CASHFLOW_ITEM_COLUMNS.name.header"
        :sortable="CASHFLOW_ITEM_COLUMNS.name.sortable"
      >
        <template #filter="{ filterModel, filterCallback }">
          <InputText v-model="filterModel.value" class="w-full" @input="filterCallback()" />
        </template>
        <template #body="{ data }">
          {{ data.name ?? '' }}
        </template>
      </Column>

      <Column
        :field="CASHFLOW_ITEM_COLUMNS.section.field"
        :header="CASHFLOW_ITEM_COLUMNS.section.header"
        :showFilterMenu="false"
      >
        <template #filter="{ filterModel, filterCallback }">
          <Select
            v-model="filterModel.value"
            :options="SECTION_OPTIONS"
            optionLabel="label"
            optionValue="value"
            class="w-full"
            @change="filterCallback()"
          />
        </template>
        <template #body="{ data }">
          {{ formatSection(data.section) }}
        </template>
      </Column>

      <Column
        :field="CASHFLOW_ITEM_COLUMNS.direction.field"
        :header="CASHFLOW_ITEM_COLUMNS.direction.header"
        :showFilterMenu="false"
      >
        <template #filter="{ filterModel, filterCallback }">
          <Select
            v-model="filterModel.value"
            :options="DIRECTION_OPTIONS"
            optionLabel="label"
            optionValue="value"
            class="w-full"
            @change="filterCallback()"
          />
        </template>
        <template #body="{ data }">
          {{ formatDirection(data.direction) }}
        </template>
      </Column>

      <Column
        :field="CASHFLOW_ITEM_COLUMNS.parent.field"
        :header="CASHFLOW_ITEM_COLUMNS.parent.header"
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
          {{ data.parent_name ?? '' }}
        </template>
      </Column>

      <Column
        :field="CASHFLOW_ITEM_COLUMNS.sortOrder.field"
        :header="CASHFLOW_ITEM_COLUMNS.sortOrder.header"
      >
        <template #body="{ data }">
          {{ data.sort_order ?? 100 }}
        </template>
      </Column>

      <Column
        :field="CASHFLOW_ITEM_COLUMNS.isActive.field"
        :header="CASHFLOW_ITEM_COLUMNS.isActive.header"
        :showFilterMenu="false"
      >
        <template #filter="{ filterModel, filterCallback }">
          <Select
            v-model="filterModel.value"
            :options="ACTIVE_OPTIONS"
            optionLabel="label"
            optionValue="value"
            class="w-full"
            @change="filterCallback()"
          />
        </template>
        <template #body="{ data }">
          {{ formatBool(data.is_active) }}
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
              @click="requestRemoveItem(data)"
            />
          </div>
        </template>
      </Column>

      <template #empty>
        <div class="text-center py-6 text-muted">Нет данных.</div>
      </template>
    </DataTable>
  </div>

  <VDialog v-model="dialogOpen" max-width="560">
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <span>{{ form.id ? 'Редактировать статью' : 'Новая статья ДДС' }}</span>
        <VBtn icon="tabler-x" variant="text" @click="dialogOpen = false" />
      </VCardTitle>
      <VCardText class="d-flex flex-column gap-4">
        <div v-if="errorMessage" class="text-sm text-error">{{ errorMessage }}</div>

        <VTextField v-model="form.code" label="Код" hide-details :disabled="isReadOnly" />
        <VTextField v-model="form.name" label="Название" hide-details :disabled="isReadOnly" />

        <VSelect
          v-model="form.section"
          :items="SECTION_OPTIONS"
          item-title="label"
          item-value="value"
          label="Раздел"
          hide-details
          :disabled="isReadOnly"
        />

        <VSelect
          v-model="form.direction"
          :items="DIRECTION_OPTIONS"
          item-title="label"
          item-value="value"
          label="Направление"
          hide-details
          :disabled="isReadOnly"
        />

        <VSelect
          v-model="form.parent_id"
          :items="parentOptions"
          item-title="label"
          item-value="id"
          label="Родитель"
          clearable
          hide-details
          :disabled="isReadOnly"
        />

        <VTextField
          v-model.number="form.sort_order"
          type="number"
          label="Сортировка"
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

  <VDialog v-model="confirmDeleteOpen" max-width="420">
    <VCard>
      <VCardTitle>Удалить статью ДДС?</VCardTitle>
      <VCardText>Статья будет удалена без восстановления.</VCardText>
      <VCardActions>
        <VSpacer />
        <VBtn variant="text" @click="confirmDeleteOpen = false">Отмена</VBtn>
        <VBtn color="error" :loading="deletingId !== null" @click="removeItem">Удалить</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
