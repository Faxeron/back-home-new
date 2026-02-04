<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { VForm } from 'vuetify/components/VForm'
import { useCookie } from '@/@core/composable/useCookie'
import { useDictionariesStore } from '@/stores/dictionaries'
import { $api } from '@/utils/api'
import { emailValidator, requiredValidator } from '@/@core/utils/validators'
import type { Estimate } from '@/modules/estimates/types/estimates.types'
import type { ContractTemplate } from '@/modules/production/types/contract-templates.types'
import { fetchContractTemplates } from '@/modules/production/api/contractTemplates.api'
import {
  createEstimateContracts,
  fetchCounterpartyDetails,
  type CounterpartyDetails,
} from '@/modules/estimates/api/estimate.api'
import AppMaskedField from '@/@core/components/app-form-elements/AppMaskedField.vue'
import AppDateTimePicker from '@/@core/components/app-form-elements/AppDateTimePicker.vue'

type UserOption = {
  id: number
  name: string
  email?: string | null
  role_codes?: string[]
}

const props = defineProps<{
  modelValue: boolean
  estimate: Estimate | null
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'created'): void
}>()

const router = useRouter()
const dictionaries = useDictionariesStore()
const userData = useCookie<any>('userData')

const numberedSteps = [
  { title: 'Договор', subtitle: 'Данные договора' },
  { title: 'Клиент', subtitle: 'Данные клиента' },
  { title: 'Монтаж', subtitle: 'Данные монтажа' },
]

const currentStep = ref(0)
const isCurrentStepValid = ref(true)
const refStepOne = ref<VForm>()
const refStepTwo = ref<VForm>()
const refStepThree = ref<VForm>()

const loadingTemplates = ref(false)
const saving = ref(false)
const errorMessage = ref('')
const validationErrors = ref<Record<string, string[]>>({})
const existingContractId = ref<number | null>(null)
const templates = ref<ContractTemplate[]>([])
const counterpartyLoading = ref(false)
const usersLoading = ref(false)
const users = ref<UserOption[]>([])

const form = reactive({
  counterparty_type: 'individual',
  counterparty: {
    phone: '',
    email: '',
    first_name: '',
    last_name: '',
    patronymic: '',
    passport_series: '',
    passport_number: '',
    passport_code: '',
    passport_whom: '',
    issued_at: '',
    legal_name: '',
    short_name: '',
    inn: '',
    kpp: '',
    ogrn: '',
    legal_address: '',
    postal_address: '',
    director_name: '',
    bank_name: '',
    bik: '',
    account_number: '',
    correspondent_account: '',
    accountant_name: '',
  },
  contract: {
    contract_date: '',
    total_amount: null as number | null,
    manager_id: null as number | null,
    measurer_id: null as number | null,
    worker_id: null as number | null,
    city_id: null as number | null,
    site_address: '',
    sale_type_id: null as number | null,
    installation_date: '',
  },
  template_ids: [] as number[],
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

const rangeDatePickerConfig = {
  ...datePickerConfig,
  mode: 'range',
}

const contractDateDefault = () => new Date().toISOString().slice(0, 10)

const validationHeader = 'Не заполнены поля:'

const fieldLabels: Record<string, string> = {
  template_ids: 'Шаблоны договоров',
  'template_ids.*': 'Шаблон договора',
  allow_uncovered: 'Разрешить непокрытые типы',
  counterparty_type: 'Тип клиента',
  'counterparty.phone': 'Телефон',
  'counterparty.email': 'Email',
  'counterparty.first_name': 'Имя',
  'counterparty.last_name': 'Фамилия',
  'counterparty.patronymic': 'Отчество',
  'counterparty.passport_series': 'Серия паспорта',
  'counterparty.passport_number': 'Номер паспорта',
  'counterparty.passport_code': 'Код подразделения',
  'counterparty.passport_whom': 'Кем выдан',
  'counterparty.issued_at': 'Дата выдачи паспорта',
  'counterparty.issued_by': 'Кем выдан (полн.)',
  'counterparty.legal_name': 'Полное наименование',
  'counterparty.short_name': 'Краткое наименование',
  'counterparty.inn': 'ИНН',
  'counterparty.kpp': 'КПП',
  'counterparty.ogrn': 'ОГРН',
  'counterparty.legal_address': 'Юридический адрес',
  'counterparty.postal_address': 'Почтовый адрес',
  'counterparty.director_name': 'Директор',
  'counterparty.bank_name': 'Банк',
  'counterparty.bik': 'БИК',
  'counterparty.account_number': 'Расчетный счет',
  'counterparty.correspondent_account': 'Корр. счет',
  'counterparty.accountant_name': 'Бухгалтер',
  'contract.contract_date': 'Дата договора',
  'contract.total_amount': 'Сумма',
  'contract.manager_id': 'Менеджер',
  'contract.measurer_id': 'Замерщик',
  'contract.worker_id': 'Монтажник',
  'contract.city_id': 'Город',
  'contract.site_address': 'Адрес объекта',
  'contract.sale_type_id': 'Тип продаж',
  'contract.installation_date': 'Период монтажа',
  'contract.work_start_date': 'Дата начала монтажа',
  'contract.work_end_date': 'Дата окончания монтажа',
}

const validationErrorList = computed(() => {
  const list = new Set<string>()
  for (const field of Object.keys(validationErrors.value || {})) {
    const normalized = field.replace(/\.\d+$/, '')
    const label = fieldLabels[field] ?? fieldLabels[normalized] ?? field
    list.add(label)
  }
  return Array.from(list)
})

const estimateLink = computed(() => {
  if (!props.estimate?.id) return null
  return props.estimate.link || `/estimates/${props.estimate.id}/edit`
})

const estimateLinkLabel = computed(() =>
  props.estimate?.id ? `Смета #${props.estimate.id}` : 'Смета',
)

const currentUserId = computed(() => {
  const raw = userData.value?.id ?? userData.value?.userId ?? null
  const id = Number(raw)
  return Number.isFinite(id) ? id : null
})

const normalizeRoleCodes = (value: unknown): string[] => {
  if (Array.isArray(value)) {
    return value
      .map(item => String(item).toLowerCase())
      .filter(Boolean)
  }
  if (typeof value === 'string' && value.trim()) {
    return [value.trim().toLowerCase()]
  }
  return []
}

const filterUsersByRoles = (roles: string[]) => {
  return users.value.filter(user =>
    roles.some(role => (user.role_codes ?? []).includes(role)),
  )
}

const managerOptions = computed<UserOption[]>(() => {
  return filterUsersByRoles(['admin', 'manager'])
})

const measurerOptions = computed<UserOption[]>(() => {
  return filterUsersByRoles(['measurer'])
})

const workerOptions = computed<UserOption[]>(() => {
  return filterUsersByRoles(['admin', 'worker'])
})

const normalizeValue = (value?: string | null) => (value ?? '').trim()
const normalizeDateValue = (value?: string | null) => {
  const trimmed = normalizeValue(value)
  if (!trimmed) return ''
  const match = trimmed.match(/^(\d{2})\.(\d{2})\.(\d{4})$/)
  if (match) {
    const [, day, month, year] = match
    return `${year}-${month}-${day}`
  }
  return trimmed
}

const normalizeRangeToken = (value: unknown) => {
  if (value instanceof Date) {
    return value.toISOString().slice(0, 10)
  }
  return String(value ?? '')
}

const parseDateRange = (value?: unknown) => {
  if (Array.isArray(value)) {
    const start = normalizeDateValue(normalizeRangeToken(value[0]))
    const end = normalizeDateValue(normalizeRangeToken(value[1] ?? value[0]))
    return { start, end }
  }
  if (value instanceof Date) {
    const start = normalizeDateValue(normalizeRangeToken(value))
    return { start, end: start }
  }
  const trimmed = normalizeValue(String(value ?? ''))
  if (!trimmed) return { start: '', end: '' }
  const parts = trimmed.split(/\s+(?:to|–|—|-)\s+/)
  const start = normalizeDateValue(parts[0] ?? '')
  const end = normalizeDateValue(parts[1] ?? parts[0] ?? '')
  return { start, end }
}

const buildCounterpartyPayload = () => {
  const base = {
    phone: normalizeValue(form.counterparty.phone),
    email: normalizeValue(form.counterparty.email),
  }

  if (form.counterparty_type === 'individual') {
    return {
      ...base,
      last_name: normalizeValue(form.counterparty.last_name),
      first_name: normalizeValue(form.counterparty.first_name),
      patronymic: normalizeValue(form.counterparty.patronymic),
      passport_series: normalizeValue(form.counterparty.passport_series),
      passport_number: normalizeValue(form.counterparty.passport_number),
      passport_code: normalizeValue(form.counterparty.passport_code),
      passport_whom: normalizeValue(form.counterparty.passport_whom),
      issued_at: normalizeDateValue(form.counterparty.issued_at),
    }
  }

  return {
    ...base,
    legal_name: normalizeValue(form.counterparty.legal_name),
    short_name: normalizeValue(form.counterparty.short_name),
    inn: normalizeValue(form.counterparty.inn),
    kpp: normalizeValue(form.counterparty.kpp),
    ogrn: normalizeValue(form.counterparty.ogrn),
    legal_address: normalizeValue(form.counterparty.legal_address),
    postal_address: normalizeValue(form.counterparty.postal_address),
    director_name: normalizeValue(form.counterparty.director_name),
    accountant_name: normalizeValue(form.counterparty.accountant_name),
    bank_name: normalizeValue(form.counterparty.bank_name),
    bik: normalizeValue(form.counterparty.bik),
    account_number: normalizeValue(form.counterparty.account_number),
    correspondent_account: normalizeValue(form.counterparty.correspondent_account),
  }
}

const buildContractPayload = () => {
  const { start, end } = parseDateRange(form.contract.installation_date)
  return {
    ...form.contract,
    manager_id: form.contract.manager_id,
    measurer_id: form.contract.measurer_id,
    worker_id: form.contract.worker_id,
    site_address: normalizeValue(form.contract.site_address),
    contract_date: normalizeDateValue(form.contract.contract_date),
    installation_date: start || normalizeDateValue(form.contract.installation_date),
    work_start_date: start || normalizeDateValue(form.contract.installation_date),
    work_end_date: end || start || normalizeDateValue(form.contract.installation_date),
  }
}

const parseName = (value: string) => {
  const parts = value.split(' ').map(part => part.trim()).filter(Boolean)
  return {
    last: parts[0] ?? '',
    first: parts[1] ?? '',
    patronymic: parts[2] ?? '',
  }
}

const resetCounterpartyForm = () => {
  form.counterparty.phone = ''
  form.counterparty.email = ''
  form.counterparty.first_name = ''
  form.counterparty.last_name = ''
  form.counterparty.patronymic = ''
  form.counterparty.passport_series = ''
  form.counterparty.passport_number = ''
  form.counterparty.passport_code = ''
  form.counterparty.passport_whom = ''
  form.counterparty.issued_at = ''
  form.counterparty.legal_name = ''
  form.counterparty.short_name = ''
  form.counterparty.inn = ''
  form.counterparty.kpp = ''
  form.counterparty.ogrn = ''
  form.counterparty.legal_address = ''
  form.counterparty.postal_address = ''
  form.counterparty.director_name = ''
  form.counterparty.bank_name = ''
  form.counterparty.bik = ''
  form.counterparty.account_number = ''
  form.counterparty.correspondent_account = ''
  form.counterparty.accountant_name = ''
}

const applyIndividualDetails = (details: CounterpartyDetails, fallbackName: string) => {
  const individual = details.individual ?? {}
  const parsed = parseName(fallbackName)

  form.counterparty.last_name = individual.last_name ?? parsed.last
  form.counterparty.first_name = individual.first_name ?? parsed.first
  form.counterparty.patronymic = individual.patronymic ?? parsed.patronymic
  form.counterparty.passport_series = individual.passport_series ?? ''
  form.counterparty.passport_number = individual.passport_number ?? ''
  form.counterparty.passport_code = individual.passport_code ?? ''
  form.counterparty.passport_whom = individual.passport_whom ?? individual.issued_by ?? ''
  form.counterparty.issued_at = individual.issued_at ?? ''
}

const applyCompanyDetails = (details: CounterpartyDetails) => {
  const company = details.company ?? {}
  const name = details.name ?? ''

  form.counterparty.legal_name = company.legal_name ?? name
  form.counterparty.short_name = company.short_name ?? name
  form.counterparty.inn = company.inn ?? ''
  form.counterparty.kpp = company.kpp ?? ''
  form.counterparty.ogrn = company.ogrn ?? ''
  form.counterparty.legal_address = company.legal_address ?? ''
  form.counterparty.postal_address = company.postal_address ?? ''
  form.counterparty.director_name = company.director_name ?? ''
  form.counterparty.accountant_name = company.accountant_name ?? ''
  form.counterparty.bank_name = company.bank_name ?? ''
  form.counterparty.bik = company.bik ?? ''
  form.counterparty.account_number = company.account_number ?? ''
  form.counterparty.correspondent_account = company.correspondent_account ?? ''
}

const applyCounterpartyDetails = (details: CounterpartyDetails) => {
  const fallbackName = details.name ?? props.estimate?.client_name ?? ''
  const isCompany =
    details.type === 'company' ||
    details.type === 'legal' ||
    Boolean(details.company)

  form.counterparty_type = isCompany ? 'company' : 'individual'
  form.counterparty.phone = details.phone ?? form.counterparty.phone
  form.counterparty.email = details.email ?? form.counterparty.email

  if (isCompany) {
    applyCompanyDetails(details)
  } else {
    applyIndividualDetails(details, fallbackName)
  }
}

const resetFormFromEstimate = () => {
  const estimate = props.estimate
  resetCounterpartyForm()

  const counterpartyType = estimate?.counterparty?.type
  form.counterparty_type = counterpartyType === 'company' || counterpartyType === 'legal'
    ? 'company'
    : 'individual'
  form.template_ids = []
  form.contract.contract_date = contractDateDefault()
  form.contract.site_address = estimate?.site_address ?? ''
  form.contract.total_amount = estimate?.total_sum ?? null
  form.contract.city_id = null
  form.contract.sale_type_id = null
  form.contract.installation_date = ''
  {
    const role = String(userData.value?.role ?? '').toLowerCase()
    form.contract.manager_id = ['admin', 'manager'].includes(role) ? currentUserId.value : null
  }
  form.contract.measurer_id = null
  form.contract.worker_id = null
  form.counterparty.phone = estimate?.client_phone ?? estimate?.counterparty?.phone ?? ''
  form.counterparty.email = estimate?.counterparty?.email ?? ''

  const nameValue = estimate?.client_name ?? estimate?.counterparty?.name ?? ''
  const { first, last, patronymic } = parseName(nameValue)
  form.counterparty.first_name = first
  form.counterparty.last_name = last
  form.counterparty.patronymic = patronymic
}

const loadCounterpartyDetails = async () => {
  const counterpartyId = props.estimate?.client_id ?? props.estimate?.counterparty?.id
  if (!counterpartyId) return

  counterpartyLoading.value = true
  try {
    const details = await fetchCounterpartyDetails(counterpartyId)
    if (details) {
      applyCounterpartyDetails(details)
    }
  } catch (error) {
    // ignore, keep estimate snapshot values
  } finally {
    counterpartyLoading.value = false
  }
}

const loadTemplates = async () => {
  loadingTemplates.value = true
  try {
    const response = await fetchContractTemplates({ per_page: 200 })
    templates.value = response?.data ?? []
  } catch (error) {
    templates.value = []
  } finally {
    loadingTemplates.value = false
  }
}

const loadUsers = async () => {
  usersLoading.value = true
  try {
    const response: any = await $api('settings/users')
    const list = Array.isArray(response?.data?.data) ? response.data.data : Array.isArray(response?.data) ? response.data : []
    users.value = list
      .map((item: any) => ({
        id: Number(item?.id),
        name: item?.name ?? item?.email ?? `User #${item?.id}`,
        email: item?.email ?? null,
        role_codes: normalizeRoleCodes(item?.role_codes ?? item?.role_code ?? item?.roles),
      }))
      .filter((item: any) => Number.isFinite(item.id) && item.name)
  } catch (error) {
    users.value = []
  } finally {
    usersLoading.value = false
  }
}

const loadDictionaries = async () => {
  await Promise.all([dictionaries.loadCities(), dictionaries.loadSaleTypes()])
}

const closeDialog = () => {
  isOpen.value = false
}

const openExistingContract = async () => {
  if (!existingContractId.value) return
  await router.push({ path: `/operations/contracts/${existingContractId.value}` })
  closeDialog()
}

const templateRules = [
  (value: number[]) => (Array.isArray(value) && value.length > 0) || 'Выберите хотя бы один шаблон договора.',
]
const sumRules = [
  (value: unknown) => requiredValidator(value),
  (value: unknown) => {
    const amount = Number(value)
    return Number.isFinite(amount) && amount > 0 || 'Укажите сумму'
  },
]
const rangeRules = [
  (value: unknown) => {
    const { start, end } = parseDateRange(value)
    return Boolean(start && end) || 'Укажите период монтажа'
  },
]

const validateStepOne = async () => {
  const result = await refStepOne.value?.validate()
  if (result?.valid) {
    isCurrentStepValid.value = true
    currentStep.value = 1
  } else {
    isCurrentStepValid.value = false
  }
}

const validateStepTwo = async () => {
  const result = await refStepTwo.value?.validate()
  if (result?.valid) {
    isCurrentStepValid.value = true
    currentStep.value = 2
  } else {
    isCurrentStepValid.value = false
  }
}

const validateStepThree = async () => {
  const result = await refStepThree.value?.validate()
  if (result?.valid) {
    isCurrentStepValid.value = true
    await submit()
  } else {
    isCurrentStepValid.value = false
  }
}

const submit = async (forceAllow = false) => {
  if (!props.estimate?.id) return
  if (!form.template_ids.length) {
    errorMessage.value = 'Выберите хотя бы один шаблон договора.'
    return
  }

  saving.value = true
  errorMessage.value = ''
  validationErrors.value = {}
  existingContractId.value = null
  try {
    await createEstimateContracts(props.estimate.id, {
      template_ids: form.template_ids,
      counterparty_type: form.counterparty_type,
      counterparty: buildCounterpartyPayload(),
      contract: buildContractPayload(),
      allow_uncovered: Boolean(forceAllow),
    })
    emit('created')
    closeDialog()
  } catch (error: any) {
    const status = error?.response?.status ?? error?.status
    const contractId = error?.data?.contract_id ?? error?.response?.data?.contract_id
    if (status === 409 && contractId) {
      existingContractId.value = Number(contractId)
      errorMessage.value = 'Договор по этой смете уже создан.'
      return
    }
    const responseErrors = error?.data?.errors ?? error?.response?.data?.errors
    if (responseErrors && typeof responseErrors === 'object') {
      validationErrors.value = responseErrors
      errorMessage.value = 'Заполните обязательные поля и попробуйте снова.'
      return
    }

    const missingTypes = error?.data?.missing_product_types ?? error?.response?.data?.missing_product_types
    if (Array.isArray(missingTypes) && missingTypes.length) {
      const names = missingTypes.map((item: any) => item?.name ?? item?.id).join(', ')
      const confirm = window.confirm(
        `Не все типы товаров покрыты шаблонами: ${names}. Продолжить?`,
      )
      if (confirm) {
        await submit(true)
        return
      }
    }
    errorMessage.value = error?.data?.message ?? error?.response?.data?.message ?? 'Не удалось создать договоры.'
  } finally {
    saving.value = false
  }
}

watch(
  () => props.modelValue,
  async value => {
    if (!value) return
    currentStep.value = 0
    isCurrentStepValid.value = true
    resetFormFromEstimate()
    errorMessage.value = ''
    validationErrors.value = {}
    existingContractId.value = null
    await Promise.all([loadTemplates(), loadDictionaries(), loadCounterpartyDetails(), loadUsers()])
  },
)
</script>

<template>
  <VDialog v-model="isOpen" max-width="980">
    <VCard>
      <VCardTitle class="d-flex align-center justify-between">
        <span>Создание договоров</span>
        <VBtn icon="tabler-x" variant="text" @click="closeDialog" />
      </VCardTitle>

      <VCardText class="d-flex flex-column gap-4">
        <div v-if="errorMessage" class="text-sm text-error">
          <div>{{ errorMessage }}</div>
          <div v-if="existingContractId" class="mt-2">
            <VBtn
              variant="text"
              size="small"
              prepend-icon="tabler-external-link"
              @click="openExistingContract"
            >
              Открыть договор
            </VBtn>
          </div>
        </div>

        <div v-if="validationErrorList.length" class="text-sm text-error mt-2">
          <div class="font-medium mb-1">{{ validationHeader }}</div>
          <ul class="pl-4 list-disc">
            <li v-for="item in validationErrorList" :key="item">{{ item }}</li>
          </ul>
        </div>

        <AppStepper
          v-model:current-step="currentStep"
          align="start"
          :items="numberedSteps"
          :is-active-step-valid="isCurrentStepValid"
        />
      </VCardText>

      <VDivider />

      <VCardText>
        <VWindow v-model="currentStep" class="disable-tab-transition">
          <VWindowItem>
            <VForm ref="refStepOne" @submit.prevent="validateStepOne">
              <VRow>
                <VCol cols="12">
                  <div class="d-flex align-center gap-2 text-sm text-muted">
                    <span>Смета:</span>
                    <VBtn
                      v-if="estimateLink"
                      :href="estimateLink"
                      target="_blank"
                      rel="noopener"
                      variant="text"
                      size="small"
                      class="px-0"
                    >
                      {{ estimateLinkLabel }}
                    </VBtn>
                    <span v-else>-</span>
                  </div>
                </VCol>

                <VCol cols="12" md="4">
                  <AppDateTimePicker
                    v-model="form.contract.contract_date"
                    label="Дата договора"
                    label-in-field
                    placeholder="ДД.ММ.ГГГГ"
                    :config="datePickerConfig"
                    :rules="[requiredValidator]"
                    hide-details="auto"
                  />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField
                    v-model.number="form.contract.total_amount"
                    type="number"
                    label="Сумма"
                    :rules="sumRules"
                    hide-details="auto"
                  />
                </VCol>
                <VCol cols="12" md="4">
                  <AppSelect
                    v-model="form.contract.manager_id"
                    :items="managerOptions"
                    item-title="name"
                    item-value="id"
                    label="Менеджер"
                    label-in-field
                    :loading="usersLoading"
                    :rules="[requiredValidator]"
                    hide-details="auto"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppSelect
                    v-model="form.contract.sale_type_id"
                    :items="dictionaries.saleTypes"
                    item-title="name"
                    item-value="id"
                    label="Тип продаж"
                    label-in-field
                    :rules="[requiredValidator]"
                    hide-details="auto"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppSelect
                    v-model="form.contract.measurer_id"
                    :items="measurerOptions"
                    item-title="name"
                    item-value="id"
                    label="Замерщик"
                    label-in-field
                    clearable
                    :loading="usersLoading"
                    hide-details="auto"
                  />
                </VCol>
                <VCol cols="12">
                  <div class="text-sm font-semibold mb-2">Сформировать документы</div>
                  <div v-if="loadingTemplates" class="text-sm text-muted">Загрузка...</div>
                  <VInput
                    v-else
                    :model-value="form.template_ids"
                    :rules="templateRules"
                    hide-details="auto"
                  >
                    <VRow>
                      <VCol
                        v-for="template in templates"
                        :key="template.id"
                        cols="12"
                        md="6"
                      >
                        <VCheckbox
                          v-model="form.template_ids"
                          :value="template.id"
                          hide-details
                        >
                          <template #label>
                            <div>
                              <div class="font-medium">{{ template.short_name }}</div>
                              <div class="text-xs text-muted">{{ template.name }}</div>
                            </div>
                          </template>
                        </VCheckbox>
                      </VCol>
                    </VRow>
                  </VInput>
                </VCol>

                <VCol cols="12">
                  <div class="d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-6">
                    <VBtn color="secondary" variant="tonal" disabled>
                      <VIcon icon="tabler-arrow-left" start class="flip-in-rtl" />
                      Назад
                    </VBtn>
                    <VBtn type="submit">
                      Далее
                      <VIcon icon="tabler-arrow-right" end class="flip-in-rtl" />
                    </VBtn>
                  </div>
                </VCol>
              </VRow>
            </VForm>
          </VWindowItem>
          <VWindowItem>
            <VForm ref="refStepTwo" @submit.prevent="validateStepTwo">
              <VRow>
                <VCol v-if="counterpartyLoading" cols="12">
                  <div class="text-xs text-muted">(загрузка)</div>
                </VCol>

                <VCol cols="12">
                  <VRadioGroup v-model="form.counterparty_type" inline :rules="[requiredValidator]">
                    <VRadio label="Физлицо" value="individual" />
                    <VRadio label="Юрлицо" value="company" />
                  </VRadioGroup>
                </VCol>

                <template v-if="form.counterparty_type === 'individual'">
                  <VCol cols="12" md="4">
                    <VTextField v-model="form.counterparty.last_name" label="Фамилия" :rules="[requiredValidator]" hide-details="auto" />
                  </VCol>
                  <VCol cols="12" md="4">
                    <VTextField v-model="form.counterparty.first_name" label="Имя" :rules="[requiredValidator]" hide-details="auto" />
                  </VCol>
                  <VCol cols="12" md="4">
                    <VTextField v-model="form.counterparty.patronymic" label="Отчество" :rules="[requiredValidator]" hide-details="auto" />
                  </VCol>
                  <VCol cols="12" md="4">
                  <AppPhoneField v-model="form.counterparty.phone" label="Телефон" label-in-field placeholder="+7 000 000 00 00" :rules="[requiredValidator]" hide-details="auto" />
                  </VCol>
                  <VCol cols="12" md="4">
                  <VTextField v-model="form.counterparty.email" label="Email" :rules="[emailValidator]" hide-details="auto" />
                  </VCol>
                  <VCol cols="12" md="4">
                    <AppMaskedField
                      v-model="form.counterparty.passport_series"
                      label="Серия паспорта"
                      label-in-field
                      mask="0000"
                      placeholder="0000"
                      :rules="[requiredValidator]"
                      hide-details="auto"
                    />
                  </VCol>
                  <VCol cols="12" md="4">
                    <AppMaskedField
                      v-model="form.counterparty.passport_number"
                      label="Номер паспорта"
                      label-in-field
                      mask="000000"
                      placeholder="000000"
                      :rules="[requiredValidator]"
                      hide-details="auto"
                    />
                  </VCol>
                  <VCol cols="12" md="4">
                    <AppMaskedField
                      v-model="form.counterparty.passport_code"
                      label="Код подразделения"
                      label-in-field
                      mask="000-000"
                      placeholder="000-000"
                      :rules="[requiredValidator]"
                      hide-details="auto"
                    />
                  </VCol>
                  <VCol cols="12" md="4">
                    <VTextField v-model="form.counterparty.passport_whom" label="Кем выдан" :rules="[requiredValidator]" hide-details="auto" />
                  </VCol>
                  <VCol cols="12" md="4">
                    <AppDateTimePicker
                      v-model="form.counterparty.issued_at"
                      label="Когда выдан"
                      label-in-field
                      placeholder="ДД.ММ.ГГГГ"
                      :config="datePickerConfig"
                      :rules="[requiredValidator]"
                      hide-details="auto"
                    />
                  </VCol>
                </template>

                <template v-else>
                  <VCol cols="12" md="6">
                    <VTextField v-model="form.counterparty.legal_name" label="Полное наименование" :rules="[requiredValidator]" hide-details="auto" />
                  </VCol>
                  <VCol cols="12" md="6">
                    <VTextField v-model="form.counterparty.short_name" label="Краткое наименование" :rules="[requiredValidator]" hide-details="auto" />
                  </VCol>
                  <VCol cols="12" md="4">
                    <VTextField v-model="form.counterparty.inn" label="ИНН" :rules="[requiredValidator]" hide-details="auto" />
                  </VCol>
                  <VCol cols="12" md="4">
                    <VTextField v-model="form.counterparty.kpp" label="КПП" :rules="[requiredValidator]" hide-details="auto" />
                  </VCol>
                  <VCol cols="12" md="4">
                    <VTextField v-model="form.counterparty.ogrn" label="ОГРН" :rules="[requiredValidator]" hide-details="auto" />
                  </VCol>
                  <VCol cols="12" md="6">
                    <VTextField v-model="form.counterparty.legal_address" label="Юридический адрес" :rules="[requiredValidator]" hide-details="auto" />
                  </VCol>
                  <VCol cols="12" md="6">
                    <VTextField v-model="form.counterparty.postal_address" label="Почтовый адрес" :rules="[requiredValidator]" hide-details="auto" />
                  </VCol>
                  <VCol cols="12" md="6">
                    <VTextField v-model="form.counterparty.director_name" label="Директор" :rules="[requiredValidator]" hide-details="auto" />
                  </VCol>
                  <VCol cols="12" md="6">
                    <VTextField v-model="form.counterparty.accountant_name" label="Бухгалтер" :rules="[requiredValidator]" hide-details="auto" />
                  </VCol>
                  <VCol cols="12" md="6">
                    <VTextField v-model="form.counterparty.bank_name" label="Банк" :rules="[requiredValidator]" hide-details="auto" />
                  </VCol>
                  <VCol cols="12" md="3">
                    <VTextField v-model="form.counterparty.bik" label="БИК" :rules="[requiredValidator]" hide-details="auto" />
                  </VCol>
                  <VCol cols="12" md="3">
                    <VTextField v-model="form.counterparty.account_number" label="Счет" :rules="[requiredValidator]" hide-details="auto" />
                  </VCol>
                  <VCol cols="12" md="6">
                    <VTextField v-model="form.counterparty.correspondent_account" label="Корр. счет" :rules="[requiredValidator]" hide-details="auto" />
                  </VCol>
                  <VCol cols="12" md="6">
                  <AppPhoneField v-model="form.counterparty.phone" label="Телефон" label-in-field placeholder="+7 000 000 00 00" :rules="[requiredValidator]" hide-details="auto" />
                  </VCol>
                  <VCol cols="12" md="6">
                    <VTextField v-model="form.counterparty.email" label="Email" :rules="[requiredValidator, emailValidator]" hide-details="auto" />
                  </VCol>
                </template>

                <VCol cols="12">
                  <div class="d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-6">
                    <VBtn color="secondary" variant="tonal" @click="currentStep = 0">
                      <VIcon icon="tabler-arrow-left" start class="flip-in-rtl" />
                      Назад
                    </VBtn>
                    <VBtn type="submit">
                      Далее
                      <VIcon icon="tabler-arrow-right" end class="flip-in-rtl" />
                    </VBtn>
                  </div>
                </VCol>
              </VRow>
            </VForm>
          </VWindowItem>
          <VWindowItem>
            <VForm ref="refStepThree" @submit.prevent="validateStepThree">
              <VRow>
                <VCol cols="12" md="4">
                  <AppSelect
                    v-model="form.contract.city_id"
                    :items="dictionaries.cities"
                    item-title="name"
                    item-value="id"
                    label="Город"
                    label-in-field
                    :rules="[requiredValidator]"
                    hide-details="auto"
                  />
                </VCol>
                <VCol cols="12" md="8">
                  <VTextField v-model="form.contract.site_address" label="Адрес объекта" :rules="[requiredValidator]" hide-details="auto" />
                </VCol>
                <VCol cols="12" md="6">
                  <AppDateTimePicker
                    v-model="form.contract.installation_date"
                    label="Период монтажа"
                    label-in-field
                    placeholder="ДД.ММ.ГГГГ - ДД.ММ.ГГГГ"
                    :config="rangeDatePickerConfig"
                    :rules="rangeRules"
                    hide-details="auto"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppSelect
                    v-model="form.contract.worker_id"
                    :items="workerOptions"
                    item-title="name"
                    item-value="id"
                    label="Монтажник"
                    label-in-field
                    clearable
                    :loading="usersLoading"
                    hide-details="auto"
                  />
                </VCol>

                <VCol cols="12">
                  <div class="d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-6">
                    <VBtn color="secondary" variant="tonal" @click="currentStep = 1">
                      <VIcon icon="tabler-arrow-left" start class="flip-in-rtl" />
                      Назад
                    </VBtn>
                    <VBtn color="primary" :loading="saving" type="submit">
                      Создать договоры
                    </VBtn>
                  </div>
                </VCol>
              </VRow>
            </VForm>
          </VWindowItem>
        </VWindow>
      </VCardText>
    </VCard>
  </VDialog>
</template>
