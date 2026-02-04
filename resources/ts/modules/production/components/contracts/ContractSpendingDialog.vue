<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useDictionariesStore } from '@/stores/dictionaries'
import type { Contract } from '@/types/finance'
import { createContractSpending } from '@/modules/finance/api/spendings.api'
import AppDateTimePicker from '@/@core/components/app-form-elements/AppDateTimePicker.vue'
import AppSelect from '@/@core/components/app-form-elements/AppSelect.vue'
import CashboxCell from '@/components/cashboxes/CashboxCell.vue'

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
  fond_id: null as number | null,
  spending_item_id: null as number | null,
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

const spendingItemsForFund = computed(() => {
  if (!form.fond_id) return dictionaries.spendingItems
  return dictionaries.spendingItems.filter(item => String(item.fond_id ?? '') === String(form.fond_id))
})

const resetForm = () => {
  form.cashbox_id = null
  form.payment_method_id = null
  form.fond_id = null
  form.spending_item_id = null
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
    await createContractSpending({
      contract_id: props.contract.id,
      cashbox_id: form.cashbox_id,
      payment_method_id: form.payment_method_id,
      fond_id: form.fond_id,
      spending_item_id: form.spending_item_id,
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
    const backendMessage = error?.data?.message ?? error?.response?.data?.message
    if (backendMessage === 'Insufficient funds') {
      errorMessage.value = 'Недостаточно средств в кассе.'
    } else {
      errorMessage.value = backendMessage ?? 'Не удалось добавить расход.'
    }
  } finally {
    saving.value = false
  }
}

watch(
  () => props.modelValue,
  async value => {
    if (!value) return
    resetForm()
    await Promise.all([
      dictionaries.loadCashBoxes(),
      dictionaries.loadPaymentMethods(),
      dictionaries.loadSpendingFunds(),
      dictionaries.loadSpendingItems(),
    ])
  },
)

watch(
  () => form.fond_id,
  () => {
    if (!form.spending_item_id) return
    const exists = spendingItemsForFund.value.some(item => String(item.id) === String(form.spending_item_id))
    if (!exists) form.spending_item_id = null
  },
)
</script>

<template>
  <VDialog v-model="isOpen" max-width="560">
    <VCard>
      <VCardTitle class="d-flex align-center justify-between">
        <span>Добавить расход</span>
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
          label="?????"
        >
          <template #selection="{ item }">
            <CashboxCell :cashbox="item?.raw ?? item" size="sm" />
          </template>
          <template #item="{ props: itemProps, item }">
            <VListItem v-bind="itemProps">
              <CashboxCell :cashbox="item?.raw ?? item" size="sm" />
            </VListItem>
          </template>
        </AppSelect>

        <AppSelect
          v-model="form.payment_method_id"
          :items="dictionaries.paymentMethods"
          item-title="name"
          item-value="id"
          label="Способ оплаты"
        />

        <AppSelect
          v-model="form.fond_id"
          :items="dictionaries.spendingFunds"
          item-title="name"
          item-value="id"
          label="Фонд"
        />

        <AppSelect
          v-model="form.spending_item_id"
          :items="spendingItemsForFund"
          item-title="name"
          item-value="id"
          label="Статья"
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
