<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import CashboxBadge from '@/components/cashboxes/CashboxBadge.vue'
import { $api } from '@/utils/api'
import { CASH_BOX_COLUMNS } from '@/modules/finance/config/cashBoxesTable.config'
import type { CashBox, CashboxLogo } from '@/types/finance'

type CashBoxRow = CashBox & {
  company_name?: string
}

const props = defineProps<{
  rows: CashBox[]
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

const rows = computed<CashBoxRow[]>(() =>
  (props.rows || []).map(row => ({
    ...row,
    company_name: row.company?.name ?? '',
  })),
)

const dialogOpen = ref(false)
const saving = ref(false)
const deletingId = ref<number | null>(null)
const errorMessage = ref('')

const form = reactive({
  id: null as number | null,
  name: '',
  description: '',
  is_active: true,
})
const canSave = computed(() => (form.id ? canEdit.value : canCreate.value))
const isReadOnly = computed(() => !canSave.value)

const logoFile = ref<File | null>(null)
const logoPreview = ref<string | null>(null)
const removeLogo = ref(false)
const logoPresets = ref<CashboxLogo[]>([])
const logoPresetId = ref<number | null>(null)
const logoPresetsLoading = ref(false)

const resetForm = () => {
  form.id = null
  form.name = ''
  form.description = ''
  form.is_active = true
  logoFile.value = null
  logoPreview.value = null
  removeLogo.value = false
  logoPresetId.value = null
  errorMessage.value = ''
}

const openCreate = () => {
  if (!canCreate.value) return
  resetForm()
  loadLogoPresets()
  dialogOpen.value = true
}

const openEdit = (row: CashBoxRow) => {
  if (!canEdit.value) return
  resetForm()
  form.id = row.id
  form.name = row.name ?? ''
  form.description = row.description ?? ''
  form.is_active = row.is_active ?? true
  logoPreview.value = row.logo_url ?? null
  logoPresetId.value = row.logo_preset_id ?? null
  loadLogoPresets()
  dialogOpen.value = true
}

const normalizeLogoFile = (value: File | File[] | null) => {
  if (Array.isArray(value)) return value[0] ?? null
  return value ?? null
}

const handleLogoChange = (value: File | File[] | null) => {
  logoFile.value = normalizeLogoFile(value)
  if (logoFile.value) {
    logoPreview.value = URL.createObjectURL(logoFile.value)
    removeLogo.value = false
    logoPresetId.value = null
  }
}

const handlePresetChange = (value: number | null) => {
  logoPresetId.value = value
  if (value) {
    logoFile.value = null
    logoPreview.value = null
    removeLogo.value = false
  }
}

const loadLogoPresets = async () => {
  if (logoPresetsLoading.value || logoPresets.value.length) return
  logoPresetsLoading.value = true
  try {
    const response = await $api('settings/cashbox-logos')
    logoPresets.value = response?.data ?? []
  } catch (error) {
    logoPresets.value = []
  } finally {
    logoPresetsLoading.value = false
  }
}

watch(dialogOpen, value => {
  if (!value && logoPreview.value && logoFile.value) {
    URL.revokeObjectURL(logoPreview.value)
  }
})

const submit = async () => {
  if (!canSave.value) return
  if (!form.name.trim()) {
    errorMessage.value = 'Укажите название кассы.'
    return
  }

  saving.value = true
  errorMessage.value = ''
  try {
    const body = new FormData()
    body.append('name', form.name.trim())
    body.append('description', form.description ?? '')
    body.append('is_active', form.is_active ? '1' : '0')
    if (logoFile.value) body.append('logo', logoFile.value)
    if (removeLogo.value) body.append('logo_remove', '1')
    if (logoPresetId.value) {
      body.append('logo_source', 'preset')
      body.append('logo_preset_id', String(logoPresetId.value))
    } else if (logoFile.value) {
      body.append('logo_source', 'custom')
    }

    if (form.id) {
      body.append('_method', 'PATCH')
      await $api(`settings/cash-boxes/${form.id}`, {
        method: 'POST',
        body,
      })
    } else {
      await $api('settings/cash-boxes', {
        method: 'POST',
        body,
      })
    }

    dialogOpen.value = false
    emit('reload')
  } catch (error: any) {
    errorMessage.value = error?.data?.message ?? error?.response?.data?.message ?? 'Не удалось сохранить кассу.'
  } finally {
    saving.value = false
  }
}

const removeCashbox = async (row: CashBoxRow) => {
  if (!canDelete.value) return
  if (!window.confirm('Удалить кассу?')) return
  deletingId.value = row.id
  try {
    await $api(`settings/cash-boxes/${row.id}`, { method: 'DELETE' })
    emit('reload')
  } catch (error: any) {
    errorMessage.value = error?.data?.message ?? error?.response?.data?.message ?? 'Не удалось удалить кассу.'
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
              label="Создать кассу"
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
        :field="CASH_BOX_COLUMNS.id.field"
        :header="CASH_BOX_COLUMNS.id.header"
        :sortable="CASH_BOX_COLUMNS.id.sortable"
        :style="`inline-size: ${CASH_BOX_COLUMNS.id.width};`"
        :headerStyle="`inline-size: ${CASH_BOX_COLUMNS.id.width};`"
        :bodyStyle="`inline-size: ${CASH_BOX_COLUMNS.id.width};`"
      >
        <template #filter="{ filterModel, filterCallback }">
          <InputText
            v-if="filterModel"
            v-model="filterModel.value"
            class="w-full"
            @input="filterCallback()"
          />
        </template>
      </Column>

      <Column :header="CASH_BOX_COLUMNS.logo.header" :style="`inline-size: ${CASH_BOX_COLUMNS.logo.width};`">
        <template #body="{ data }">
          <CashboxBadge :cashbox="data" :show-name="false" size="sm" />
        </template>
      </Column>

      <Column
        :field="CASH_BOX_COLUMNS.name.field"
        :header="CASH_BOX_COLUMNS.name.header"
        :sortable="CASH_BOX_COLUMNS.name.sortable"
      >
        <template #filter="{ filterModel, filterCallback }">
          <InputText v-model="filterModel.value" class="w-full" @input="filterCallback()" />
        </template>
        <template #body="{ data }">
          {{ data.name ?? '\u2014' }}
        </template>
      </Column>

      <Column :field="CASH_BOX_COLUMNS.company.field" :header="CASH_BOX_COLUMNS.company.header">
        <template #body="{ data }">
          {{ data.company_name ?? '' }}
        </template>
      </Column>

      <Column :field="CASH_BOX_COLUMNS.description.field" :header="CASH_BOX_COLUMNS.description.header">
        <template #body="{ data }">
          {{ data.description ?? '' }}
        </template>
      </Column>

      <Column :field="CASH_BOX_COLUMNS.isActive.field" :header="CASH_BOX_COLUMNS.isActive.header">
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
              @click="removeCashbox(data)"
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
        <span>{{ form.id ? 'Редактировать кассу' : 'Новая касса' }}</span>
        <VBtn icon="tabler-x" variant="text" @click="dialogOpen = false" />
      </VCardTitle>
      <VCardText class="d-flex flex-column gap-4">
        <div v-if="errorMessage" class="text-sm text-error">{{ errorMessage }}</div>

        <VTextField v-model="form.name" label="Название" hide-details :disabled="isReadOnly" />
        <VTextarea v-model="form.description" label="Описание" rows="2" auto-grow hide-details :disabled="isReadOnly" />
        <VSwitch v-model="form.is_active" label="Активна" inset :disabled="isReadOnly" />

        <div class="d-flex flex-column gap-2">
          <div class="text-sm font-medium">Логотип</div>
          <VSelect
            v-model="logoPresetId"
            :items="logoPresets"
            item-title="name"
            item-value="id"
            label="Выбрать логотип"
            no-data-text="Нет логотипов"
            :loading="logoPresetsLoading"
            clearable
            hide-details
            :disabled="isReadOnly"
            @update:modelValue="handlePresetChange"
          >
            <template #selection="{ item }">
              <div class="logo-option">
                <img v-if="item?.raw?.logo_url" :src="item.raw.logo_url" alt="" />
                <span>{{ item?.raw?.name ?? item?.title }}</span>
              </div>
            </template>
            <template #item="{ item, props: itemProps }">
              <div v-bind="itemProps" class="logo-option w-full">
                <img v-if="item?.raw?.logo_url" :src="item.raw.logo_url" alt="" />
                <span>{{ item?.raw?.name ?? item?.title }}</span>
              </div>
            </template>
          </VSelect>
          <div class="d-flex align-center gap-3">
            <CashboxBadge
              :name="form.name || 'Касса'"
              :logo-url="logoPreview"
              :show-name="false"
              size="md"
            />
            <VFileInput
              label="Загрузить PNG"
              accept="image/png"
              prepend-icon="tabler-upload"
              :model-value="logoFile"
              :disabled="Boolean(logoPresetId) || isReadOnly"
              @update:modelValue="handleLogoChange"
            />
          </div>
          <VSwitch
            v-model="removeLogo"
            label="Удалить текущий логотип"
            inset
            :disabled="!logoPreview || isReadOnly"
          />
        </div>
      </VCardText>
      <VCardActions class="justify-end gap-2">
        <VBtn variant="text" @click="dialogOpen = false">Отмена</VBtn>
        <VBtn color="primary" :loading="saving" :disabled="isReadOnly" @click="submit">Сохранить</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>

.logo-option {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.logo-option img {
  width: 28px;
  height: 18px;
  object-fit: contain;
  border-radius: 4px;
  background: rgba(0, 0, 0, 0.06);
}
</style>
