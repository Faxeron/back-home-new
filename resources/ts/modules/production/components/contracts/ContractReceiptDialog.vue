<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useDictionariesStore } from '@/stores/dictionaries'
import type { Contract } from '@/types/finance'
import { createContractReceipt } from '@/modules/finance/api/receipts.api'
import AppDateTimePicker from '@/@core/components/app-form-elements/AppDateTimePicker.vue'
import AppSelect from '@/@core/components/app-form-elements/AppSelect.vue'
import CashboxCell from '@/components/cashboxes/CashboxCell.vue'

const props = defineProps<{
  modelValue: boolean
  contract: Contract | null
  simple?: boolean
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
  cashflow_item_id: null as number | null,
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
  form.cashflow_item_id = null
  form.sum = null
  form.payment_date = new Date().toISOString().slice(0, 10)
  form.description = ''
  errorMessage.value = ''
  validationErrors.value = {}
}

const operatingInCashflowItems = computed(() =>
  dictionaries.cashflowItems
    .filter(item => item.section === 'OPERATING' && item.direction === 'IN' && item.is_active !== false)
    .sort((a, b) => Number(a.sort_order ?? 0) - Number(b.sort_order ?? 0)),
)

const dialogTitle = computed(() => (props.simple ? 'Добавить оплату' : 'Добавить приход'))
const submitButtonLabel = computed(() => (props.simple ? 'Добавить оплату' : 'Добавить'))

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

const resolveDefaultCashflowItemId = () => {
  const items = operatingInCashflowItems.value
  if (!items.length) return null

  const byCode = items.find(item => String(item.code ?? '').toUpperCase().includes('ADVANCE'))
  if (byCode?.id !== undefined && byCode?.id !== null) return Number(byCode.id)

  const byName = items.find(item => String(item.name ?? '').toLowerCase().includes('аванс'))
  if (byName?.id !== undefined && byName?.id !== null) return Number(byName.id)

  const first = items[0]?.id
  return first !== undefined && first !== null ? Number(first) : null
}

const applyDefaults = () => {
  const firstPaymentMethodId = dictionaries.paymentMethods[0]?.id
  if (!form.payment_method_id && firstPaymentMethodId !== undefined && firstPaymentMethodId !== null) {
    form.payment_method_id = Number(firstPaymentMethodId)
  }

  if (!form.cashflow_item_id) {
    form.cashflow_item_id = resolveDefaultCashflowItemId()
  }
}

const submit = async () => {
  if (!props.contract?.id) return
  const paymentMethodId = form.payment_method_id ?? (dictionaries.paymentMethods[0]?.id ? Number(dictionaries.paymentMethods[0].id) : null)
  if (!paymentMethodId) {
    errorMessage.value = 'Не найден способ оплаты.'
    return
  }

  saving.value = true
  errorMessage.value = ''
  validationErrors.value = {}
  try {
    await createContractReceipt({
      contract_id: props.contract.id,
      cashbox_id: form.cashbox_id,
      payment_method_id: paymentMethodId,
      cashflow_item_id: form.cashflow_item_id,
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
    await Promise.all([
      dictionaries.loadCashBoxes(),
      dictionaries.loadPaymentMethods(),
      dictionaries.loadCashflowItems(),
    ])
    applyDefaults()
  },
)
</script>

<template>
  <VDialog v-model="isOpen" max-width="520">
    <VCard>
      <VCardTitle class="d-flex align-center justify-between">
        <span>{{ dialogTitle }}</span>
        <VBtn icon="tabler-x" variant="text" @click="isOpen = false" />
      </VCardTitle>
      <VCardText class="d-flex flex-column gap-3">
        <div v-if="errorMessage" class="text-sm text-error">
          {{ errorMessage }}
        </div>

        <div v-if="Object.keys(validationErrors).length" class="text-sm text-error">
          Проверьте обязательные поля.
        </div>

        <VTextField
          v-model.number="form.sum"
          type="number"
          label="Сумма"
          hide-details
        />

        <AppSelect
          v-model="form.cashbox_id"
          :items="dictionaries.cashBoxes"
          item-title="name"
          item-value="id"
          label="Касса"
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

        <AppDateTimePicker
          v-model="form.payment_date"
          label="Дата"
          placeholder="ДД.ММ.ГГГГ"
          :config="datePickerConfig"
          hide-details
        />

        <AppSelect
          v-model="form.cashflow_item_id"
          :items="operatingInCashflowItems"
          item-title="name"
          item-value="id"
          label="Статья ДДС"
        />

        <AppSelect
          v-if="!props.simple"
          v-model="form.payment_method_id"
          :items="dictionaries.paymentMethods"
          item-title="name"
          item-value="id"
          label="Способ оплаты"
        />

        <VTextarea
          v-if="!props.simple"
          v-model="form.description"
          label="Описание"
          rows="2"
          auto-grow
          hide-details
        />
      </VCardText>
      <VCardActions class="justify-end gap-2">
        <VBtn variant="text" @click="isOpen = false">Отмена</VBtn>
        <VBtn color="primary" :loading="saving" @click="submit">{{ submitButtonLabel }}</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
