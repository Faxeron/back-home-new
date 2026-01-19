<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useDictionariesStore } from '@/stores/dictionaries'
import type { Contract } from '@/types/finance'
import { createContractReceipt } from '@/modules/finance/api/receipts.api'
import AppDateTimePicker from '@/@core/components/app-form-elements/AppDateTimePicker.vue'
import AppSelect from '@/@core/components/app-form-elements/AppSelect.vue'

const props = defineProps<{
  modelValue: boolean
  contract: Contract | null
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'created'): void
}>()

const dictionaries = useDictionariesStore()
const saving = ref(false)
const errorMessage = ref('')
const validationErrors = ref<Record<string, string[]>>({})

const form = reactive({
  cashbox_id: null as number | null,
  payment_method_id: null as number | null,
  sum: null as number | null,
  payment_date: '',
  description: '',
})

const isOpen = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
})

const datePickerConfig = {
  altInput: true,
  altFormat: 'd.m.Y',
  dateFormat: 'Y-m-d',
  allowInput: true,
  clickOpens: true,
}

const resetForm = () => {
  form.cashbox_id = null
  form.payment_method_id = null
  form.sum = null
  form.payment_date = new Date().toISOString().slice(0, 10)
  form.description = ''
  errorMessage.value = ''
  validationErrors.value = {}
}

const normalizeDateValue = (value?: string | null) => {
  const trimmed = (value ?? '').trim()
  if (!trimmed) return ''
  const match = trimmed.match(/^(\d{2})\.(\d{2})\.(\d{4})$/)
  if (match) {
    const [, day, month, year] = match
    return `${year}-${month}-${day}`
  }
  return trimmed
}

const submit = async () => {
  if (!props.contract?.id) return
  saving.value = true
  errorMessage.value = ''
  validationErrors.value = {}
  try {
    await createContractReceipt({
      contract_id: props.contract.id,
      cashbox_id: form.cashbox_id,
      payment_method_id: form.payment_method_id,
      sum: form.sum,
      payment_date: normalizeDateValue(form.payment_date),
      description: form.description || null,
      counterparty_id: props.contract.counterparty_id ?? null,
    })
    emit('created')
    isOpen.value = false
  } catch (error: any) {
    const responseErrors = error?.data?.errors ?? error?.response?.data?.errors
    if (responseErrors && typeof responseErrors === 'object') {
      validationErrors.value = responseErrors
      errorMessage.value = 'Проверьте обязательные поля.'
      return
    }
    errorMessage.value = error?.data?.message ?? error?.response?.data?.message ?? 'Не удалось добавить приход.'
  } finally {
    saving.value = false
  }
}

watch(
  () => props.modelValue,
  async value => {
    if (!value) return
    resetForm()
    await Promise.all([dictionaries.loadCashBoxes(), dictionaries.loadPaymentMethods()])
  },
)
</script>

<template>
  <VDialog v-model="isOpen" max-width="520">
    <VCard>
      <VCardTitle class="d-flex align-center justify-between">
        <span>Добавить приход</span>
        <VBtn icon="tabler-x" variant="text" @click="isOpen = false" />
      </VCardTitle>
      <VCardText class="d-flex flex-column gap-3">
        <div v-if="errorMessage" class="text-sm text-error">
          {{ errorMessage }}
        </div>

        <div v-if="Object.keys(validationErrors).length" class="text-sm text-error">
          Проверьте обязательные поля.
        </div>

        <AppSelect
          v-model="form.cashbox_id"
          :items="dictionaries.cashBoxes"
          item-title="name"
          item-value="id"
          label="Касса"
        />

        <AppSelect
          v-model="form.payment_method_id"
          :items="dictionaries.paymentMethods"
          item-title="name"
          item-value="id"
          label="Способ оплаты"
        />

        <VTextField
          v-model.number="form.sum"
          type="number"
          label="Сумма"
          hide-details
        />

        <AppDateTimePicker
          v-model="form.payment_date"
          label="Дата оплаты"
          placeholder="ДД.ММ.ГГГГ"
          :config="datePickerConfig"
          hide-details
        />

        <VTextarea
          v-model="form.description"
          label="Описание"
          rows="2"
          auto-grow
          hide-details
        />
      </VCardText>
      <VCardActions class="justify-end gap-2">
        <VBtn variant="text" @click="isOpen = false">Отмена</VBtn>
        <VBtn color="primary" :loading="saving" @click="submit">Добавить</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
