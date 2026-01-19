<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useDictionariesStore } from '@/stores/dictionaries'
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

const loadingTemplates = ref(false)
const saving = ref(false)
const errorMessage = ref('')
const validationErrors = ref<Record<string, string[]>>({})
const existingContractId = ref<number | null>(null)
const templates = ref<ContractTemplate[]>([])
const counterpartyLoading = ref(false)

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
  'contract.city_id': 'Город',
  'contract.site_address': 'Адрес объекта',
  'contract.sale_type_id': 'Тип продаж',
  'contract.installation_date': 'Дата монтажа',
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

const parseDateRange = (value?: string | null) => {
  const trimmed = normalizeValue(value)
  if (!trimmed) return { start: '', end: '' }
  let parts = trimmed.split(' to ')
  if (parts.length === 1) {
    parts = trimmed.split(' - ')
  }
  if (parts.length === 1) {
    parts = trimmed.split(' — ')
  }
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
    resetFormFromEstimate()
    errorMessage.value = ''
    validationErrors.value = {}
    existingContractId.value = null
    await Promise.all([loadTemplates(), loadDictionaries(), loadCounterpartyDetails()])
  },
)
</script>

<template>
  <VDialog v-model="isOpen" max-width="920">
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

        <VCard variant="outlined">
          <VCardText>
            <div class="text-sm font-semibold mb-3">Данные договора</div>
            <VRow>
              <VCol cols="12" md="4">
                <AppDateTimePicker
                  v-model="form.contract.contract_date"
                  label="Дата договора"
                  placeholder="ДД.ММ.ГГГГ"
                  :config="datePickerConfig"
                  hide-details
                />
              </VCol>
              <VCol cols="12" md="4">
                <VTextField
                  v-model.number="form.contract.total_amount"
                  type="number"
                  label="Сумма"
                  hide-details
                />
              </VCol>
              <VCol cols="12" md="4">
                <AppSelect
                  v-model="form.contract.city_id"
                  :items="dictionaries.cities"
                  item-title="name"
                  item-value="id"
                  label="Город"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField v-model="form.contract.site_address" label="Адрес объекта" hide-details />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="form.contract.sale_type_id"
                  :items="dictionaries.saleTypes"
                  item-title="name"
                  item-value="id"
                  label="Тип продаж"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppDateTimePicker
                  v-model="form.contract.installation_date"
                  label="Период монтажа"
                  placeholder="ДД.ММ.ГГГГ - ДД.ММ.ГГГГ"
                  :config="rangeDatePickerConfig"
                  hide-details
                />
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <VCard variant="outlined">
          <VCardText>
            <div class="text-sm font-semibold mb-3">
              Клиент
              <span v-if="counterpartyLoading" class="text-xs text-muted ml-2">(загрузка)</span>
            </div>
            <VRow>
              <VCol cols="12">
                <VRadioGroup v-model="form.counterparty_type" inline>
                  <VRadio label="Физлицо" value="individual" />
                  <VRadio label="Юрлицо" value="company" />
                </VRadioGroup>
              </VCol>

              <template v-if="form.counterparty_type === 'individual'">
                <VCol cols="12" md="4">
                  <VTextField v-model="form.counterparty.last_name" label="Фамилия" hide-details />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model="form.counterparty.first_name" label="Имя" hide-details />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model="form.counterparty.patronymic" label="Отчество" hide-details />
                </VCol>
                <VCol cols="12" md="4">
                  <AppPhoneField v-model="form.counterparty.phone" placeholder="+7 000 000 00 00" hide-details />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model="form.counterparty.email" label="Email" hide-details />
                </VCol>
                <VCol cols="12" md="4">
                  <AppMaskedField
                    v-model="form.counterparty.passport_series"
                    label="Серия паспорта"
                    mask="0000"
                    placeholder="0000"
                    hide-details
                  />
                </VCol>
                <VCol cols="12" md="4">
                  <AppMaskedField
                    v-model="form.counterparty.passport_number"
                    label="Номер паспорта"
                    mask="000000"
                    placeholder="000000"
                    hide-details
                  />
                </VCol>
                <VCol cols="12" md="4">
                  <AppMaskedField
                    v-model="form.counterparty.passport_code"
                    label="Код подразделения"
                    mask="000-000"
                    placeholder="000-000"
                    hide-details
                  />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model="form.counterparty.passport_whom" label="Кем выдан" hide-details />
                </VCol>
                <VCol cols="12" md="4">
                  <AppDateTimePicker
                    v-model="form.counterparty.issued_at"
                    label="Когда выдан"
                    placeholder="ДД.ММ.ГГГГ"
                    :config="datePickerConfig"
                    hide-details
                  />
                </VCol>
              </template>

              <template v-else>
                <VCol cols="12" md="6">
                  <VTextField v-model="form.counterparty.legal_name" label="Полное наименование" hide-details />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField v-model="form.counterparty.short_name" label="Краткое наименование" hide-details />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model="form.counterparty.inn" label="ИНН" hide-details />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model="form.counterparty.kpp" label="КПП" hide-details />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField v-model="form.counterparty.ogrn" label="ОГРН" hide-details />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField v-model="form.counterparty.legal_address" label="Юридический адрес" hide-details />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField v-model="form.counterparty.postal_address" label="Почтовый адрес" hide-details />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField v-model="form.counterparty.director_name" label="Директор" hide-details />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField v-model="form.counterparty.accountant_name" label="Бухгалтер" hide-details />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField v-model="form.counterparty.bank_name" label="Банк" hide-details />
                </VCol>
                <VCol cols="12" md="3">
                  <VTextField v-model="form.counterparty.bik" label="БИК" hide-details />
                </VCol>
                <VCol cols="12" md="3">
                  <VTextField v-model="form.counterparty.account_number" label="Счет" hide-details />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField v-model="form.counterparty.correspondent_account" label="Корр. счет" hide-details />
                </VCol>
                <VCol cols="12" md="6">
                  <AppPhoneField v-model="form.counterparty.phone" placeholder="+7 000 000 00 00" hide-details />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField v-model="form.counterparty.email" label="Email" hide-details />
                </VCol>
              </template>
            </VRow>
          </VCardText>
        </VCard>

        <VCard variant="outlined">
          <VCardText>
            <div class="text-sm font-semibold mb-3">Шаблоны договоров</div>
            <div v-if="loadingTemplates" class="text-sm text-muted">Загрузка...</div>
            <VRow v-else>
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
          </VCardText>
        </VCard>
      </VCardText>
      <VCardActions class="justify-end gap-2">
        <VBtn variant="text" @click="closeDialog">Отмена</VBtn>
        <VBtn color="primary" :loading="saving" @click="submit">Создать договоры</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
