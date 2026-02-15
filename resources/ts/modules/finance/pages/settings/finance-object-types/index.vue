<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { listFinanceObjectTypes, patchFinanceObjectTypeSettings } from '@/modules/finance/api/finance-objects.api'
import type { FinanceObjectTypeView } from '@/types/finance'

definePage({
  meta: {
    action: 'view',
    subject: 'finance',
  },
})

type EditableTypeRow = FinanceObjectTypeView & {
  edit_name: string
  edit_icon: string
  edit_sort_order: number | null
  edit_is_enabled: boolean
  base_name: string
  base_icon: string
  base_sort_order: number
  base_is_enabled: boolean
}

const loading = ref(false)
const savingKey = ref<string | null>(null)
const errorMessage = ref('')
const successMessage = ref('')
const rows = ref<EditableTypeRow[]>([])
const iconDialogOpen = ref(false)
const iconDialogKey = ref<string | null>(null)
const iconSearch = ref('')

const legacyIconMap: Record<string, string> = {
  'lucide:briefcase': 'tabler-briefcase',
  'lucide:folder-kanban': 'tabler-folder',
  'lucide:calendar-days': 'tabler-calendar-event',
  'lucide:shopping-bag': 'tabler-shopping-bag',
  'lucide:repeat': 'tabler-repeat',
  'lucide:file-check': 'tabler-file-check',
  'lucide:wrench': 'tabler-tool',
  'lucide:building-2': 'tabler-building',
  'lucide:archive': 'tabler-archive',
}

const iconOptions = [
  'tabler-briefcase',
  'tabler-folder',
  'tabler-calendar-event',
  'tabler-shopping-bag',
  'tabler-repeat',
  'tabler-file-check',
  'tabler-tool',
  'tabler-building',
  'tabler-archive',
  'tabler-coins',
  'tabler-cash',
  'tabler-bank',
  'tabler-receipt',
  'tabler-chart-bar',
  'tabler-target',
  'tabler-speakerphone',
  'tabler-bullhorn',
  'tabler-users',
  'tabler-truck',
  'tabler-settings',
  'tabler-hammer',
  'tabler-device-desktop',
  'tabler-notes',
  'tabler-clipboard-text',
  'tabler-rocket',
  'tabler-shield-check',
  'tabler-file-text',
  'tabler-package',
  'tabler-database',
  'tabler-star',
]

const sortedRows = computed(() =>
  [...rows.value].sort((a, b) => {
    if (a.edit_sort_order !== b.edit_sort_order) {
      return Number(a.edit_sort_order ?? 0) - Number(b.edit_sort_order ?? 0)
    }
    return a.key.localeCompare(b.key)
  }),
)

const activeIconRow = computed(() =>
  rows.value.find(row => row.key === iconDialogKey.value) ?? null,
)

const filteredIconOptions = computed(() => {
  const search = iconSearch.value.trim().toLowerCase()
  const filtered = iconOptions.filter(icon => icon.toLowerCase().includes(search))
  const current = activeIconRow.value?.edit_icon?.trim()
  if (current && !filtered.includes(current)) {
    filtered.unshift(current)
  }
  return filtered
})

const toEditableRow = (row: FinanceObjectTypeView): EditableTypeRow => ({
  ...row,
  edit_name: row.name ?? '',
  edit_icon: row.icon ?? '',
  edit_sort_order: row.sort_order ?? null,
  edit_is_enabled: Boolean(row.is_enabled),
  base_name: row.name ?? '',
  base_icon: row.icon ?? '',
  base_sort_order: row.sort_order ?? 0,
  base_is_enabled: Boolean(row.is_enabled),
})

const isDirty = (row: EditableTypeRow) =>
  row.edit_name.trim() !== row.base_name.trim()
  || row.edit_icon.trim() !== row.base_icon.trim()
  || Number(row.edit_sort_order ?? 0) !== Number(row.base_sort_order ?? 0)
  || row.edit_is_enabled !== row.base_is_enabled

const resolveIconClass = (icon: string | null | undefined): string => {
  const value = icon?.trim() ?? ''
  if (!value) {
    return 'tabler-circle'
  }
  return legacyIconMap[value] ?? value
}

const openIconDialog = (row: EditableTypeRow) => {
  iconDialogKey.value = row.key
  iconSearch.value = ''
  iconDialogOpen.value = true
}

const closeIconDialog = () => {
  iconDialogOpen.value = false
  iconDialogKey.value = null
  iconSearch.value = ''
}

const selectIcon = (icon: string) => {
  if (!activeIconRow.value) return
  activeIconRow.value.edit_icon = icon
  closeIconDialog()
}

const clearIcon = () => {
  if (!activeIconRow.value) return
  activeIconRow.value.edit_icon = ''
  closeIconDialog()
}

const isSelectedIcon = (icon: string): boolean => {
  if (!activeIconRow.value) return false
  return resolveIconClass(activeIconRow.value.edit_icon) === resolveIconClass(icon)
}

const loadRows = async () => {
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const response: any = await listFinanceObjectTypes({ include_disabled: 1 })
    const list = Array.isArray(response?.data) ? response.data : Array.isArray(response) ? response : []
    rows.value = list.map((row: FinanceObjectTypeView) => toEditableRow(row))
  } catch (error: any) {
    errorMessage.value =
      error?.data?.message
      ?? error?.response?.data?.message
      ?? 'Не удалось загрузить типы объектов учета.'
  } finally {
    loading.value = false
  }
}

const resetRow = (row: EditableTypeRow) => {
  row.edit_name = row.base_name
  row.edit_icon = row.base_icon
  row.edit_sort_order = row.base_sort_order
  row.edit_is_enabled = row.base_is_enabled
}

const saveRow = async (row: EditableTypeRow) => {
  savingKey.value = row.key
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const response: any = await patchFinanceObjectTypeSettings(row.key, {
      is_enabled: row.edit_is_enabled,
      name_ru: row.edit_name.trim() === '' ? null : row.edit_name.trim(),
      icon: row.edit_icon.trim() === '' ? null : row.edit_icon.trim(),
      sort_order: row.edit_sort_order === null ? null : Number(row.edit_sort_order),
    })

    const updated: FinanceObjectTypeView | null = response?.data ?? response ?? null
    if (!updated) {
      throw new Error('empty_response')
    }

    row.name = updated.name
    row.icon = updated.icon ?? null
    row.sort_order = updated.sort_order
    row.is_enabled = updated.is_enabled

    row.edit_name = updated.name
    row.edit_icon = updated.icon ?? ''
    row.edit_sort_order = updated.sort_order
    row.edit_is_enabled = updated.is_enabled

    row.base_name = updated.name
    row.base_icon = updated.icon ?? ''
    row.base_sort_order = updated.sort_order
    row.base_is_enabled = updated.is_enabled

    successMessage.value = `Тип ${row.key} сохранен.`
  } catch (error: any) {
    errorMessage.value =
      error?.data?.message
      ?? error?.response?.data?.message
      ?? `Не удалось сохранить тип ${row.key}.`
  } finally {
    savingKey.value = null
  }
}

onMounted(loadRows)
</script>

<template>
  <VCard>
    <VCardTitle class="d-flex align-center justify-space-between gap-3">
      <div class="d-flex flex-column">
        <span>Типы объектов учета</span>
        <span class="text-body-2 text-medium-emphasis">Настройки на уровне компании: включение, название, иконка, порядок.</span>
      </div>
      <VBtn variant="text" :loading="loading" @click="loadRows">Обновить</VBtn>
    </VCardTitle>

    <VCardText class="d-flex flex-column gap-3">
      <VAlert v-if="errorMessage" type="error" variant="tonal">{{ errorMessage }}</VAlert>
      <VAlert v-if="successMessage" type="success" variant="tonal">{{ successMessage }}</VAlert>
      <VAlert type="info" variant="tonal">
        Отключить все типы нельзя. Минимум один тип должен оставаться включенным.
      </VAlert>

      <VProgressLinear v-if="loading" indeterminate color="primary" />

      <VTable density="comfortable">
        <thead>
          <tr>
            <th style="inline-size: 150px;">Ключ</th>
            <th style="inline-size: 180px;">Включен</th>
            <th>Название</th>
            <th>Иконка</th>
            <th style="inline-size: 180px;">Порядок</th>
            <th style="inline-size: 200px;">Действия</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in sortedRows" :key="row.key">
            <td>
              <div class="font-weight-medium">{{ row.key }}</div>
            </td>
            <td>
              <VSwitch
                v-model="row.edit_is_enabled"
                color="primary"
                hide-details
                inset
              />
            </td>
            <td>
              <VTextField
                v-model="row.edit_name"
                density="compact"
                hide-details
                placeholder="Название в UI"
              />
            </td>
            <td>
              <div class="d-flex align-center gap-2">
                <VBtn
                  size="small"
                  variant="tonal"
                  class="text-none"
                  @click="openIconDialog(row)"
                >
                  <VIcon :icon="resolveIconClass(row.edit_icon)" size="18" class="me-2" />
                  Выбрать
                </VBtn>
                <VBtn
                  size="small"
                  variant="text"
                  class="text-none"
                  :disabled="!row.edit_icon"
                  @click="row.edit_icon = ''"
                >
                  Очистить
                </VBtn>
              </div>
            </td>
            <td>
              <VTextField
                v-model.number="row.edit_sort_order"
                density="compact"
                type="number"
                hide-details
              />
            </td>
            <td>
              <div class="d-flex gap-2">
                <VBtn
                  size="small"
                  color="primary"
                  :loading="savingKey === row.key"
                  :disabled="!isDirty(row)"
                  @click="saveRow(row)"
                >
                  Сохранить
                </VBtn>
                <VBtn
                  size="small"
                  variant="text"
                  :disabled="!isDirty(row)"
                  @click="resetRow(row)"
                >
                  Сброс
                </VBtn>
              </div>
            </td>
          </tr>
          <tr v-if="!sortedRows.length && !loading">
            <td colspan="6" class="text-center py-6 text-medium-emphasis">Нет данных</td>
          </tr>
        </tbody>
      </VTable>
    </VCardText>
  </VCard>

  <VDialog v-model="iconDialogOpen" max-width="980">
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between gap-3">
        <span>Выберите иконку</span>
        <VChip v-if="activeIconRow" size="small" variant="tonal">{{ activeIconRow.key }}</VChip>
      </VCardTitle>
      <VCardText class="d-flex flex-column gap-3">
        <VTextField
          v-model="iconSearch"
          density="compact"
          hide-details
          placeholder="Поиск по имени иконки..."
          prepend-inner-icon="tabler-search"
        />

        <div class="finance-object-icon-grid d-flex flex-wrap gap-2">
          <VBtn
            v-for="icon in filteredIconOptions"
            :key="icon"
            size="small"
            class="finance-object-icon-btn text-none"
            :variant="isSelectedIcon(icon) ? 'flat' : 'tonal'"
            :color="isSelectedIcon(icon) ? 'primary' : undefined"
            @click="selectIcon(icon)"
          >
            <VIcon :icon="resolveIconClass(icon)" size="20" />
            <span class="text-caption">{{ icon }}</span>
          </VBtn>
        </div>
      </VCardText>
      <VCardActions class="justify-space-between">
        <VBtn variant="text" :disabled="!activeIconRow?.edit_icon" @click="clearIcon">Очистить</VBtn>
        <VBtn variant="text" @click="closeIconDialog">Закрыть</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.finance-object-icon-grid {
  max-block-size: 460px;
  overflow: auto;
  padding-inline-end: 4px;
}

.finance-object-icon-btn {
  justify-content: flex-start;
  min-inline-size: 170px;
}
</style>
