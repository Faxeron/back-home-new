<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { $api } from '@/utils/api'
import { useAppSnackbarStore } from '@/stores/appSnackbar'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { formatSum } from '@/utils/formatters/finance'

type PayrollRuleRow = {
  id?: number
  user_id: number | null
  document_type: 'supply' | 'install' | 'combined'
  fixed_amount: number
  margin_percent: number
  is_active: boolean
  isNew?: boolean
  saving?: boolean
}

type UserOption = {
  id: number
  name?: string | null
  email?: string | null
}

type PayrollAccrualRow = {
  id: number
  contract_id?: number | null
  contract_document_id?: number | null
  document_type?: string | null
  type?: string | null
  amount?: number | null
  paid_amount?: number | null
  paid_at?: string | null
  created_at?: string | null
  user?: { id: number; name?: string | null; email?: string | null } | null
}

type PayrollAccrualPayRow = PayrollAccrualRow & {
  selected: boolean
  pay_amount: number | null
}

type CashboxOption = { id: number; name?: string | null }

type PaymentMethodOption = { id: number; name?: string | null }

type SpendingFundOption = { id: number; name?: string | null }

type SpendingItemOption = { id: number; name?: string | null; fond_id?: number | null }

type PayrollPayoutRow = {
  id: number
  payout_date?: string | null
  total_amount?: number | null
  user?: { id: number; name?: string | null; email?: string | null } | null
  cashbox?: { id: number; name?: string | null } | null
  item?: { id: number; name?: string | null } | null
}

const activeTab = ref<'rules' | 'accruals' | 'payouts'>('rules')
const rules = ref<PayrollRuleRow[]>([])
const rulesLoading = ref(false)
const rulesSavingAll = ref(false)
const users = ref<UserOption[]>([])
const usersLoading = ref(false)
const accruals = ref<PayrollAccrualRow[]>([])
const accrualsLoading = ref(false)
const accrualsError = ref('')

const payoutUserId = ref<number | null>(null)
const payoutCashboxId = ref<number | null>(null)
const payoutPaymentMethodId = ref<number | null>(null)
const payoutFundId = ref<number>(2)
const payoutItemId = ref<number | null>(72)
const payoutDate = ref<string>('')
const payoutComment = ref('')
const payoutAccruals = ref<PayrollAccrualPayRow[]>([])
const payoutAccrualsLoading = ref(false)
const payoutError = ref('')
const payoutSubmitting = ref(false)
const payouts = ref<PayrollPayoutRow[]>([])
const payoutsLoading = ref(false)

const cashboxes = ref<CashboxOption[]>([])
const paymentMethods = ref<PaymentMethodOption[]>([])
const funds = ref<SpendingFundOption[]>([])
const spendingItems = ref<SpendingItemOption[]>([])

const appSnackbar = useAppSnackbarStore()

const showSnackbar = (text: string, color: 'success' | 'error' = 'success') => {
  appSnackbar.show(text, color)
}

const documentTypeOptions = [
  { title: 'Поставка', value: 'supply' },
  { title: 'Монтаж', value: 'install' },
  { title: 'Совмещенный', value: 'combined' },
]

const payrollTypeLabel = (value?: string | null) => {
  if (!value) return '-'
  if (value === 'fixed') return 'Фикс за договор'
  if (value === 'margin_percent') return 'Процент от маржи'
  if (value === 'bonus') return 'Бонус'
  if (value === 'penalty') return 'Штраф'
  return value
}

const payrollStatusLabel = (value?: string | null) => {
  if (!value) return '-'
  if (value === 'active') return 'Создано'
  if (value === 'paid') return 'Оплачено'
  if (value === 'cancelled') return 'Отменено'
  return value
}

const payrollStatusSeverity = (value?: string | null) => {
  if (value === 'paid') return 'success'
  if (value === 'cancelled') return 'danger'
  return 'secondary'
}

const documentTypeLabel = (value?: string | null) => {
  if (!value) return '-'
  const found = documentTypeOptions.find(item => item.value === value)
  return found?.title ?? value
}

const formatDateTime = (value?: string | null) => {
  if (!value) return '-'
  return value.slice(0, 19).replace('T', ' ')
}

const formatMoney = (value?: number | null) => formatSum(value ?? 0)

const normalizeDateForApi = (value?: string | null) => {
  if (!value) return ''
  const trimmed = value.trim()
  if (!trimmed) return ''
  if (/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) return trimmed
  const match = trimmed.match(/^(\d{2})\.(\d{2})\.(\d{4})$/)
  if (match) {
    return `${match[3]}-${match[2]}-${match[1]}`
  }
  return trimmed
}

const extractFirstError = (payload?: any) => {
  const errors = payload?.errors
  if (!errors || typeof errors !== 'object') return null
  const firstKey = Object.keys(errors)[0]
  const firstValue = firstKey ? errors[firstKey] : null
  if (Array.isArray(firstValue) && firstValue[0]) return firstValue[0]
  if (typeof firstValue === 'string') return firstValue
  return null
}

const remainingFor = (row: PayrollAccrualRow) => {
  const amount = Number(row.amount ?? 0)
  const paid = Number(row.paid_amount ?? 0)
  return Math.max(0, amount - paid)
}

const loadUsers = async () => {
  usersLoading.value = true
  try {
    const response: any = await $api('settings/users')
    users.value = response?.data ?? []
  } catch (error) {
    users.value = []
  } finally {
    usersLoading.value = false
  }
}

const loadRules = async () => {
  rulesLoading.value = true
  try {
    const response: any = await $api('settings/payroll-rules')
    const data = Array.isArray(response?.data) ? response.data : []
    rules.value = data.map((row: any) => ({
      id: row.id,
      user_id: row.user_id ?? null,
      document_type: row.document_type ?? 'combined',
      fixed_amount: Number(row.fixed_amount ?? 0),
      margin_percent: Number(row.margin_percent ?? 0),
      is_active: Boolean(row.is_active),
    }))
  } catch (error) {
    rules.value = []
  } finally {
    rulesLoading.value = false
  }
}

const loadAccruals = async () => {
  accrualsLoading.value = true
  accrualsError.value = ''
  try {
    const response: any = await $api('settings/payroll-accruals')
    accruals.value = response?.data ?? []
  } catch (error: any) {
    accrualsError.value = error?.response?.data?.message ?? 'Не удалось загрузить начисления.'
  } finally {
    accrualsLoading.value = false
  }
}

const loadCashboxes = async () => {
  try {
    const response: any = await $api('finance/cashboxes')
    cashboxes.value = response?.data ?? []
  } catch (error) {
    cashboxes.value = []
  }
}

const loadPaymentMethods = async () => {
  try {
    const response: any = await $api('finance/payment-methods')
    paymentMethods.value = response?.data ?? []
  } catch (error) {
    paymentMethods.value = []
  }
}

const loadFunds = async () => {
  try {
    const response: any = await $api('finance/funds')
    funds.value = response?.data ?? []
  } catch (error) {
    funds.value = []
  }
}

const loadSpendingItems = async () => {
  try {
    const response: any = await $api('finance/spending-items')
    spendingItems.value = response?.data ?? []
  } catch (error) {
    spendingItems.value = []
  }
}

const loadPayoutAccruals = async () => {
  payoutAccrualsLoading.value = true
  payoutError.value = ''
  try {
    const response: any = await $api('settings/payroll-accruals', {
      query: {
        user_id: payoutUserId.value ?? undefined,
        unpaid_only: 1,
      },
    })
    const data = response?.data ?? []
    payoutAccruals.value = data.map((row: PayrollAccrualRow) => {
      const remaining = remainingFor(row)
      return {
        ...row,
        selected: remaining > 0,
        pay_amount: remaining,
      }
    })
  } catch (error: any) {
    payoutError.value = error?.response?.data?.message ?? 'Не удалось загрузить начисления.'
    payoutAccruals.value = []
  } finally {
    payoutAccrualsLoading.value = false
  }
}

const loadPayouts = async () => {
  payoutsLoading.value = true
  try {
    const response: any = await $api('settings/payroll-payouts', {
      query: {
        user_id: payoutUserId.value ?? undefined,
      },
    })
    payouts.value = response?.data ?? []
  } catch (error) {
    payouts.value = []
  } finally {
    payoutsLoading.value = false
  }
}

const addRuleRow = () => {
  rules.value.unshift({
    user_id: null,
    document_type: 'combined',
    fixed_amount: 0,
    margin_percent: 0,
    is_active: true,
    isNew: true,
  })
}

const saveRule = async (row: PayrollRuleRow, options: { silent?: boolean } = {}) => {
  if (!row.user_id) {
    if (!options.silent) showSnackbar('Выберите менеджера.', 'error')
    return
  }
  row.saving = true
  try {
    const payload = {
      user_id: row.user_id,
      document_type: row.document_type,
      fixed_amount: row.fixed_amount,
      margin_percent: row.margin_percent,
      is_active: row.is_active,
    }

    if (row.id) {
      const response: any = await $api(`settings/payroll-rules/${row.id}`, {
        method: 'PUT',
        body: payload,
      })
      const data = response?.data ?? response
      row.fixed_amount = Number(data?.fixed_amount ?? row.fixed_amount)
      row.margin_percent = Number(data?.margin_percent ?? row.margin_percent)
      row.is_active = Boolean(data?.is_active ?? row.is_active)
    } else {
      const response: any = await $api('settings/payroll-rules', {
        method: 'POST',
        body: payload,
      })
      const data = response?.data ?? response
      row.id = data?.id
      row.isNew = false
    }
    if (!options.silent) showSnackbar('Правило сохранено.', 'success')
  } catch (error: any) {
    const message = error?.response?.data?.message ?? 'Не удалось сохранить правило.'
    if (!options.silent) showSnackbar(message, 'error')
  } finally {
    row.saving = false
  }
}

const saveAllRules = async () => {
  if (!rules.value.length) {
    showSnackbar('Нет правил для сохранения.', 'error')
    return
  }
  rulesSavingAll.value = true
  try {
    for (const row of rules.value) {
      await saveRule(row, { silent: true })
    }
    showSnackbar('Все правила сохранены.', 'success')
  } catch (error: any) {
    const message = error?.response?.data?.message ?? 'Не удалось сохранить правила.'
    showSnackbar(message, 'error')
  } finally {
    rulesSavingAll.value = false
  }
}

const deleteRule = async (row: PayrollRuleRow) => {
  if (!row.id) {
    rules.value = rules.value.filter(item => item !== row)
    return
  }
  try {
    await $api(`settings/payroll-rules/${row.id}`, { method: 'DELETE' })
    rules.value = rules.value.filter(item => item.id !== row.id)
    showSnackbar('Правило удалено.', 'success')
  } catch (error: any) {
    const message = error?.response?.data?.message ?? 'Не удалось удалить правило.'
    showSnackbar(message, 'error')
  }
}

const accrualsTotal = computed(() =>
  accruals.value.reduce((sum, row) => sum + (Number(row.amount ?? 0) || 0), 0),
)

const payoutItemsPayload = computed(() =>
  payoutAccruals.value
    .filter(row => row.selected && Number(row.pay_amount ?? 0) > 0)
    .map(row => ({
      accrual_id: row.id,
      amount: Number(row.pay_amount ?? 0),
    })),
)

const payoutTotal = computed(() =>
  payoutItemsPayload.value.reduce((sum, row) => sum + (Number(row.amount ?? 0) || 0), 0),
)

const submitPayout = async () => {
  payoutError.value = ''

  if (!payoutUserId.value) {
    payoutError.value = 'Выберите менеджера.'
    return
  }
  if (!payoutCashboxId.value) {
    payoutError.value = 'Выберите кассу.'
    return
  }
  if (!payoutPaymentMethodId.value) {
    payoutError.value = 'Выберите способ оплаты.'
    return
  }
  if (!payoutItemId.value) {
    payoutError.value = 'Выберите статью.'
    return
  }
  if (!payoutDate.value) {
    payoutError.value = 'Укажите дату выплаты.'
    return
  }
  if (payoutItemsPayload.value.length === 0) {
    payoutError.value = 'Выберите начисления для выплаты.'
    return
  }

  payoutSubmitting.value = true
  try {
    const normalizedDate = normalizeDateForApi(payoutDate.value)
    if (!normalizedDate) {
      payoutError.value = 'Укажите корректную дату выплаты.'
      return
    }
    await $api('settings/payroll-payouts', {
      method: 'POST',
      body: {
        user_id: payoutUserId.value,
        cashbox_id: payoutCashboxId.value,
        payment_method_id: payoutPaymentMethodId.value,
        fund_id: payoutFundId.value,
        spending_item_id: payoutItemId.value,
        payout_date: normalizedDate,
        comment: payoutComment.value || null,
        items: payoutItemsPayload.value,
      },
    })
    showSnackbar('Выплата создана.', 'success')
    await loadPayoutAccruals()
    await loadPayouts()
  } catch (error: any) {
    const payload = error?.response?.data ?? error?.data
    const message =
      extractFirstError(payload) ??
      payload?.message ??
      'Не удалось создать выплату.'
    payoutError.value = message
    showSnackbar(message, 'error')
  } finally {
    payoutSubmitting.value = false
  }
}

const deletePayout = async (row: PayrollPayoutRow) => {
  if (!row.id) return
  try {
    await $api(`settings/payroll-payouts/${row.id}`, { method: 'DELETE' })
    showSnackbar('Выплата удалена.', 'success')
    await loadPayoutAccruals()
    await loadPayouts()
  } catch (error: any) {
    const message = error?.response?.data?.message ?? 'Не удалось удалить выплату.'
    showSnackbar(message, 'error')
  }
}

const fundOptions = computed(() => funds.value)
const spendingItemOptions = computed(() =>
  spendingItems.value.filter(item => Number(item.fond_id ?? item.fund_id ?? 0) === payoutFundId.value),
)

const ensurePayoutDefaults = () => {
  if (!payoutDate.value) {
    payoutDate.value = new Date().toISOString().slice(0, 10)
  }
  if (!payoutPaymentMethodId.value || !paymentMethods.value.find(item => item.id === payoutPaymentMethodId.value)) {
    payoutPaymentMethodId.value = paymentMethods.value[0]?.id ?? null
  }
  if (!payoutItemId.value || !spendingItemOptions.value.find(item => item.id === payoutItemId.value)) {
    const managerItem = spendingItemOptions.value.find(item => item.id === 72)
    payoutItemId.value = managerItem?.id ?? spendingItemOptions.value[0]?.id ?? null
  }
}

watch(payoutUserId, async () => {
  if (!payoutUserId.value) {
    payoutAccruals.value = []
    payouts.value = []
    return
  }
  await loadPayoutAccruals()
  await loadPayouts()
})

watch(spendingItemOptions, () => {
  ensurePayoutDefaults()
})

watch(paymentMethods, () => {
  ensurePayoutDefaults()
})

onMounted(async () => {
  await loadUsers()
  await loadRules()
  await loadAccruals()
  await loadCashboxes()
  await loadPaymentMethods()
  await loadFunds()
  await loadSpendingItems()
  ensurePayoutDefaults()
})
</script>

<template>
  <VCard>
    <VCardTitle>Настройки зарплаты</VCardTitle>
    <VCardText>
      <VTabs v-model="activeTab" class="mb-4">
        <VTab value="rules">Правила менеджеров</VTab>
        <VTab value="accruals">Начисления</VTab>
        <VTab value="payouts">Выплаты</VTab>
      </VTabs>

      <VWindow v-model="activeTab">
        <VWindowItem value="rules">
          <div class="flex flex-wrap items-center gap-2 mb-3">
            <VBtn color="primary" @click="addRuleRow">Добавить правило</VBtn>
            <VBtn color="success" :loading="rulesSavingAll" @click="saveAllRules">Сохранить все</VBtn>
          </div>
          <div v-if="rulesLoading" class="text-sm text-muted">Загрузка...</div>
          <div v-else class="payroll-rules-table">
            <VTable class="text-no-wrap">
              <thead>
                <tr>
                  <th>Менеджер</th>
                  <th>Документ</th>
                  <th>Фикс</th>
                  <th>% от маржи</th>
                  <th>Активен</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in rules" :key="row.id ?? `new-${row.user_id}`">
                  <td style="min-width: 260px;">
                    <VAutocomplete
                      v-model="row.user_id"
                      :items="users"
                      item-title="name"
                      item-value="id"
                      :loading="usersLoading"
                      label="Менеджер"
                      hide-details
                    />
                  </td>
                  <td style="min-width: 200px;">
                    <VSelect
                      v-model="row.document_type"
                      :items="documentTypeOptions"
                      item-title="title"
                      item-value="value"
                      label="Документ"
                      hide-details
                    />
                  </td>
                  <td style="min-width: 140px;">
                    <VTextField v-model.number="row.fixed_amount" type="number" label="Фикс" hide-details />
                  </td>
                  <td style="min-width: 160px;">
                    <VTextField v-model.number="row.margin_percent" type="number" label="% от маржи" hide-details />
                  </td>
                  <td style="width: 90px;">
                    <VSwitch v-model="row.is_active" inset hide-details />
                  </td>
                  <td style="width: 56px;">
                    <VBtn variant="text" color="error" icon="tabler-trash" @click="deleteRule(row)" />
                  </td>
                </tr>
                <tr v-if="!rules.length">
                  <td colspan="6" class="text-center text-muted py-4">Правил нет.</td>
                </tr>
              </tbody>
            </VTable>
          </div>
        </VWindowItem>

        <VWindowItem value="accruals">
          <div v-if="accrualsError" class="text-sm" style="color: #b91c1c;">
            {{ accrualsError }}
          </div>
          <div v-if="accrualsLoading" class="text-sm text-muted">Загрузка...</div>
          <DataTable
            v-else
            :value="accruals"
            dataKey="id"
            class="p-datatable-sm"
          >
            <Column field="created_at" header="Дата" style="inline-size: 18ch;">
              <template #body="{ data: row }">
                {{ formatDateTime(row.created_at) }}
              </template>
            </Column>
            <Column field="contract_id" header="Договор" style="inline-size: 10ch;">
              <template #body="{ data: row }">
                {{ row.contract_id ?? '-' }}
              </template>
            </Column>
            <Column field="document_type" header="Документ" style="inline-size: 16ch;">
              <template #body="{ data: row }">
                {{ documentTypeLabel(row.document_type) }}
              </template>
            </Column>
            <Column field="type" header="Тип" style="inline-size: 18ch;">
              <template #body="{ data: row }">
                {{ payrollTypeLabel(row.type) }}
              </template>
            </Column>
            <Column field="amount" header="Сумма" style="inline-size: 14ch;">
              <template #body="{ data: row }">
                {{ formatMoney(row.amount) }}
              </template>
            </Column>
            <Column field="paid_amount" header="Оплачено" style="inline-size: 14ch;">
              <template #body="{ data: row }">
                {{ formatMoney(row.paid_amount) }}
              </template>
            </Column>
            <Column field="status" header="Статус" style="inline-size: 12ch;">
              <template #body="{ data: row }">
                <VChip :color="payrollStatusSeverity(row.status)" size="small">
                  {{ payrollStatusLabel(row.status) }}
                </VChip>
              </template>
            </Column>
            <Column field="user" header="Кому">
              <template #body="{ data: row }">
                {{ row.user?.name ?? row.user?.email ?? '-' }}
              </template>
            </Column>
            <template #empty>
              <div class="text-center py-6 text-muted">Начислений нет.</div>
            </template>
          </DataTable>
          <VDivider class="my-4" />
          <div class="flex justify-end text-sm font-semibold">
            Сумма: {{ formatMoney(accrualsTotal) }}
          </div>
        </VWindowItem>

        <VWindowItem value="payouts">
          <VRow class="payroll-payouts-row">
            <VCol cols="12" md="4">
              <VAutocomplete
                v-model="payoutUserId"
                :items="users"
                item-title="name"
                item-value="id"
                :loading="usersLoading"
                label="Менеджер"
                placeholder="Выберите менеджера"
                hide-details
                class="payroll-label-fix"
              />
            </VCol>
            <VCol cols="12" md="4">
              <VSelect
                v-model="payoutCashboxId"
                :items="cashboxes"
                item-title="name"
                item-value="id"
                label="Касса"
                placeholder="Выберите кассу"
                hide-details
                class="payroll-label-fix"
              />
            </VCol>
            <VCol cols="12" md="4">
              <VSelect
                v-model="payoutPaymentMethodId"
                :items="paymentMethods"
                item-title="name"
                item-value="id"
                label="Способ оплаты"
                placeholder="Выберите способ оплаты"
                hide-details
                class="payroll-label-fix"
              />
            </VCol>
            <VCol cols="12" md="4">
              <VSelect
                v-model="payoutFundId"
                :items="fundOptions"
                item-title="name"
                item-value="id"
                label="Фонд"
                hide-details
                disabled
                class="payroll-label-fix"
              />
            </VCol>
            <VCol cols="12" md="4">
              <VSelect
                v-model="payoutItemId"
                :items="spendingItemOptions"
                item-title="name"
                item-value="id"
                label="Статья"
                placeholder="Выберите статью"
                hide-details
                class="payroll-label-fix"
              />
            </VCol>
            <VCol cols="12" md="4">
              <VTextField
                v-model="payoutDate"
                type="date"
                label="Дата выплаты"
                hide-details
                class="payroll-label-fix"
              />
            </VCol>
            <VCol cols="12">
              <VTextField v-model="payoutComment" label="Комментарий" hide-details class="payroll-label-fix" />
            </VCol>
          </VRow>

          <VAlert
            v-if="payoutError"
            type="error"
            variant="tonal"
            class="mt-3"
          >
            {{ payoutError }}
          </VAlert>

          <div class="flex flex-wrap items-center gap-3 mt-4">
            <VBtn color="success" :loading="payoutSubmitting" @click="submitPayout">Сформировать выплату</VBtn>
            <div class="text-sm text-muted">
              К выплате: {{ formatMoney(payoutTotal) }}
            </div>
          </div>

          <div v-if="payoutAccrualsLoading" class="text-sm text-muted mt-3">Загрузка...</div>
          <DataTable
            v-else
            :value="payoutAccruals"
            dataKey="id"
            class="p-datatable-sm mt-3"
          >
            <Column header="" style="inline-size: 4ch;">
              <template #body="{ data: row }">
                <VCheckbox v-model="row.selected" hide-details />
              </template>
            </Column>
            <Column field="created_at" header="Дата" style="inline-size: 18ch;">
              <template #body="{ data: row }">
                {{ formatDateTime(row.created_at) }}
              </template>
            </Column>
            <Column field="contract_id" header="Договор" style="inline-size: 10ch;">
              <template #body="{ data: row }">
                {{ row.contract_id ?? '-' }}
              </template>
            </Column>
            <Column field="document_type" header="Документ" style="inline-size: 16ch;">
              <template #body="{ data: row }">
                {{ documentTypeLabel(row.document_type) }}
              </template>
            </Column>
            <Column field="type" header="Тип" style="inline-size: 16ch;">
              <template #body="{ data: row }">
                {{ payrollTypeLabel(row.type) }}
              </template>
            </Column>
            <Column field="amount" header="Начислено" style="inline-size: 14ch;">
              <template #body="{ data: row }">
                {{ formatMoney(row.amount) }}
              </template>
            </Column>
            <Column field="paid_amount" header="Оплачено" style="inline-size: 14ch;">
              <template #body="{ data: row }">
                {{ formatMoney(row.paid_amount) }}
              </template>
            </Column>
            <Column header="К выплате" style="inline-size: 14ch;">
              <template #body="{ data: row }">
                <VTextField
                  v-model.number="row.pay_amount"
                  type="number"
                  min="0"
                  hide-details
                  :disabled="remainingFor(row) === 0"
                />
              </template>
            </Column>
            <template #empty>
              <div class="text-center py-6 text-muted">Нет начислений для выплаты.</div>
            </template>
          </DataTable>

          <VDivider class="my-6" />
          <h4 class="text-subtitle-1 mb-3">История выплат</h4>
          <div v-if="payoutsLoading" class="text-sm text-muted">Загрузка...</div>
          <DataTable
            v-else
            :value="payouts"
            dataKey="id"
            class="p-datatable-sm"
          >
            <Column field="payout_date" header="Дата" style="inline-size: 14ch;">
              <template #body="{ data: row }">
                {{ row.payout_date ?? '-' }}
              </template>
            </Column>
            <Column field="total_amount" header="Сумма" style="inline-size: 14ch;">
              <template #body="{ data: row }">
                {{ formatMoney(row.total_amount) }}
              </template>
            </Column>
            <Column field="cashbox" header="Касса">
              <template #body="{ data: row }">
                {{ row.cashbox?.name ?? '-' }}
              </template>
            </Column>
            <Column field="item" header="Статья">
              <template #body="{ data: row }">
                {{ row.item?.name ?? '-' }}
              </template>
            </Column>
            <Column header="Действия" style="inline-size: 10ch;">
              <template #body="{ data: row }">
                <VBtn variant="text" color="error" icon="tabler-trash" @click="deletePayout(row)" />
              </template>
            </Column>
            <template #empty>
              <div class="text-center py-6 text-muted">Выплат нет.</div>
            </template>
          </DataTable>
        </VWindowItem>
      </VWindow>
    </VCardText>
  </VCard>

  
</template>

<style scoped>
.payroll-label-fix :deep(.v-field-label),
.payroll-label-fix :deep(.v-field-label--floating) {
  background: rgb(var(--v-theme-surface));
  padding: 0 6px;
  margin-left: 4px;
}

.payroll-label-fix :deep(.v-field) {
  overflow: visible;
}

.payroll-payouts-row {
  padding-top: 6px;
}
</style>
