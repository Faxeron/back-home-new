<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import AppDateTimePicker from '@/@core/components/app-form-elements/AppDateTimePicker.vue'
import type { InstallationRow } from '../../types/installations.types'

type UserOption = { id: number; name?: string | null; email?: string | null }

const props = defineProps<{
  modelValue: boolean
  row: InstallationRow | null
  users: UserOption[]
  loading?: boolean
  currentUserId?: number | null
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'save', payload: { contractId: number; work_done_date: string; worker_id: number }): void
}>()

const formDate = ref('')
const formWorker = ref<number | null>(null)

const datePickerConfig = {
  dateFormat: 'Y-m-d',
  locale: 'ru',
}

watch(
  () => props.row,
  row => {
    formDate.value = row?.work_done_date || row?.work_start_date || ''
    formWorker.value = row?.worker_id ?? props.currentUserId ?? null
  },
  { immediate: true },
)

const isValid = computed(() => Boolean(formDate.value && formWorker.value))

const close = () => emit('update:modelValue', false)

const save = () => {
  if (!props.row || !formWorker.value || !formDate.value) return
  emit('save', {
    contractId: props.row.contract_id,
    work_done_date: formDate.value,
    worker_id: formWorker.value,
  })
}
</script>

<template>
  <VDialog
    :model-value="modelValue"
    max-width="520"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <VCard>
      <VCardTitle class="text-lg font-semibold">Назначить дату монтажа</VCardTitle>
      <VCardText class="d-flex flex-column gap-4">
        <AppDateTimePicker
          v-model="formDate"
          label="Дата монтажа"
          :config="datePickerConfig"
          clearable
        />
        <AppSelect
          v-model="formWorker"
          label="Монтажник"
          :items="users"
          item-title="name"
          item-value="id"
          :item-props="item => ({ title: item.raw.name || item.raw.email || `#${item.raw.id}` })"
          clearable
        />
      </VCardText>
      <VCardActions class="justify-end gap-2">
        <VBtn variant="text" @click="close">Отмена</VBtn>
        <VBtn
          color="primary"
          :loading="loading"
          :disabled="!isValid || loading"
          @click="save"
        >
          Назначить
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
