<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useAbility } from '@casl/vue'
import { useRoute, useRouter } from 'vue-router'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Divider from 'primevue/divider'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import { $api } from '@/utils/api'
import { formatSum } from '@/utils/formatters/finance'
import type { Contract, Receipt, Spending } from '@/types/finance'
import { useCookie } from '@/@core/composable/useCookie'
import { useDictionariesStore } from '@/stores/dictionaries'
import AppDateTimePicker from '@/@core/components/app-form-elements/AppDateTimePicker.vue'
import AppSelect from '@/@core/components/app-form-elements/AppSelect.vue'
import CashboxCell from '@/components/cashboxes/CashboxCell.vue'

type ContractDocument = {
  id: number
  file_path: string
  file_name?: string | null
  file_size?: number | null
  template_name?: string | null
  document_type?: string | null
  number_suffix?: string | null
  version?: number | null
  is_current?: boolean
  generated_at?: string | null
  created_at?: string | null
}

type SpendingDraft = Spending & {
  isNew?: boolean
  isDirty?: boolean
  payment_method_id?: number | null
}

const DOCUMENT_TYPE_LABELS: Record<string, string> = {
  supply: 'Поставка',
  install: 'Монтаж',
  combined: 'Совмещенный',
}
const PAYROLL_TYPE_LABELS: Record<string, string> = {
  fixed: 'Фикс за договор',
  margin_percent: 'Процент от маржи',
  bonus: 'Бонус',
  penalty: 'Штраф',
}
const PAYROLL_SOURCE_LABELS: Record<string, string> = {
  system: 'Система',
  manual: 'Вручную',
}
const PAYROLL_STATUS_LABELS: Record<string, string> = {
  active: 'Создано',
  paid: 'Оплачено',
  cancelled: 'Отменено',
}
const PAYROLL_TYPE_OPTIONS = [
  { title: 'Бонус', value: 'bonus' },
  { title: 'Штраф', value: 'penalty' },
]

const route = useRoute()
const router = useRouter()
const userData = useCookie<any>('userData')
const dictionaries = useDictionariesStore()
const ability = useAbility()
const canEditContract = computed(() => ability.can('edit', 'contracts'))
const canDeleteContract = computed(() => ability.can('delete', 'contracts'))
const canCreateContractDocs = computed(() => ability.can('create', 'contracts'))
const canDeleteContractDocs = computed(() => ability.can('delete', 'contracts'))
const canViewFinance = computed(() => ability.can('view', 'finance'))
const canCreateFinance = computed(() => ability.can('create', 'finance'))
const canDeleteFinance = computed(() => ability.can('delete', 'finance'))
const canViewPayroll = computed(() => ability.can('view', 'payroll'))
const canCreatePayroll = computed(() => ability.can('create', 'payroll'))
const canEditPayroll = computed(() => ability.can('edit', 'payroll'))

const contractId = computed(() => {
  const raw = route.params.id
  return Array.isArray(raw) ? raw[0] : raw
})

const contract = ref<Contract | null>(null)
type ContractHistoryItem = {
  id: string | number
  created_at?: string | null
  title?: string | null
  user?: { id: number; name?: string | null; email?: string | null } | null
}
type ContractAnalysisRow = {
  category: string
  client: number
  planned: number
  actual: number
  delta: number
}
type ContractAnalysisTotals = {
  client: number
  planned: number
  actual: number
  delta: number
}
type ContractAnalysisResponse = {
  rows: ContractAnalysisRow[]
  totals: ContractAnalysisTotals
  meta?: {
    contract_total?: number
    margin?: number
    settings?: {
      manager_fixed?: number
      manager_percent?: number
      measurer_fixed?: number
      measurer_percent?: number
    }
  }
}
type MarginSettings = {
  red_max: number
  orange_max: number
}
type PayrollAccrualRow = {
  id: number
  type?: string | null
  document_type?: string | null
  base_amount?: number | null
  percent?: number | null
  amount?: number | null
  status?: string | null
  source?: string | null
  comment?: string | null
  created_at?: string | null
  created_by?: { id: number; name?: string | null; email?: string | null } | null
  user?: { id: number; name?: string | null; email?: string | null } | null
}

const history = ref<ContractHistoryItem[]>([])
const documents = ref<ContractDocument[]>([])
const receipts = ref<Receipt[]>([])
const spendings = ref<Spending[]>([])
const analysisRows = ref<ContractAnalysisRow[]>([])
const analysisTotals = ref<ContractAnalysisTotals>({ client: 0, planned: 0, actual: 0, delta: 0 })
const marginSettings = ref<MarginSettings>({ red_max: 10, orange_max: 20 })
const spendingsDraft = ref<SpendingDraft[]>([])
const activeTab = ref('card')
const loading = ref(false)
const historyLoading = ref(false)
const documentsLoading = ref(false)
const receiptsLoading = ref(false)
const analysisLoading = ref(false)
const spendingsSaving = ref(false)
const spendingsLoading = ref(false)
const generatingId = ref<number | null>(null)
const downloadingId = ref<number | null>(null)
const deleting = ref(false)
const deletingSpending = ref(false)
const deletingDocumentId = ref<number | null>(null)
const confirmDeleteOpen = ref(false)
const confirmDocumentDeleteOpen = ref(false)
const pendingDocument = ref<ContractDocument | null>(null)
const snackbarOpen = ref(false)
const snackbarText = ref('')
const snackbarColor = ref<'success' | 'error'>('success')
const errorMessage = ref('')
const historyError = ref('')
const documentsError = ref('')
const receiptsError = ref('')
const spendingsError = ref('')
const analysisError = ref('')
const payrollError = ref('')
const confirmSpendingDeleteOpen = ref(false)
const pendingSpending = ref<SpendingDraft | null>(null)
let spendingTempId = -1
const editMode = ref(false)
const editSaving = ref(false)
const editError = ref('')
const payrollRows = ref<PayrollAccrualRow[]>([])
const payrollLoading = ref(false)
const payrollSaving = ref(false)
const payrollRecalcLoading = ref(false)
const payrollType = ref<'bonus' | 'penalty'>('bonus')
const payrollAmount = ref<number | null>(null)
const payrollComment = ref('')
const editForm = reactive({
  contract_date: '',
  total_amount: null as number | null,
  city_id: null as number | null,
  sale_type_id: null as number | null,
  address: '',
  work_start_date: '',
  work_end_date: '',
})

const updateEditField = (field: keyof typeof editForm, value: any) => {
  ;(editForm as any)[field] = value
}

const datePickerConfig = {
  altInput: true,
  altFormat: 'd.m.Y',
  dateFormat: 'Y-m-d',
  allowInput: true,
  clickOpens: true,
}

const formatMoney = (value?: number | null) => {
  if (value === null || value === undefined) return '-'
  return formatSum(value)
}

const formatPercent = (value?: number | null) => {
  if (value === null || value === undefined || Number.isNaN(value)) return '-'
  return `${value.toFixed(2)}%`
}

const marginColor = (value?: number | null) => {
  if (value === null || value === undefined || Number.isNaN(value)) {
    return 'rgba(var(--v-theme-on-surface), 0.7)'
  }
  const redMax = marginSettings.value.red_max ?? 10
  const orangeMax = marginSettings.value.orange_max ?? 20
  if (value < redMax) return 'rgb(var(--v-theme-error))'
  if (value < orangeMax) return 'rgb(var(--v-theme-warning))'
  return 'rgb(var(--v-theme-success))'
}

const planMinusFactColor = computed(() =>
  planMinusFact.value >= 0 ? 'rgb(var(--v-theme-success))' : 'rgb(var(--v-theme-error))',
)

const formatDateTime = (value?: string | null) => {
  if (!value) return '-'
  return value.slice(0, 19).replace('T', ' ')
}

const formatDate = (value?: string | null) => {
  if (!value) return '-'
  const date = value.slice(0, 10)
  const [year, month, day] = date.split('-')
  if (!year || !month || !day) return value
  return `${day}.${month}.${year}`
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

const toDateInput = (value?: string | null) => {
  if (!value) return ''
  return value.slice(0, 10)
}

const fileLabel = (path?: string | null) => {
  if (!path) return '-'
  const parts = path.split('/')
  return parts[parts.length - 1] || path
}

const formatFileSize = (value?: number | null) => {
  if (!value) return '-'
  const units = ['B', 'KB', 'MB', 'GB']
  let size = value
  let unitIndex = 0
  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024
    unitIndex++
  }
  return `${size.toFixed(unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`
}

const formatDocumentType = (doc: ContractDocument) => {
  const type = doc.document_type ?? ''
  const label = type ? (DOCUMENT_TYPE_LABELS[type] ?? type) : '-'
  const suffix = doc.number_suffix ?? ''
  return suffix ? `${label} (${suffix})` : label
}

const formatPayrollType = (row: PayrollAccrualRow) => {
  const type = row.type ?? ''
  return type ? (PAYROLL_TYPE_LABELS[type] ?? type) : '-'
}

const formatPayrollDocumentType = (row: PayrollAccrualRow) => {
  const type = row.document_type ?? ''
  return type ? (DOCUMENT_TYPE_LABELS[type] ?? type) : '-'
}

const formatPayrollSource = (row: PayrollAccrualRow) => {
  const source = row.source ?? ''
  return source ? (PAYROLL_SOURCE_LABELS[source] ?? source) : '-'
}

const formatPayrollStatus = (row: PayrollAccrualRow) => {
  const status = row.status ?? ''
  return status ? (PAYROLL_STATUS_LABELS[status] ?? status) : '-'
}

const showSnackbar = (text: string, color: 'success' | 'error' = 'success') => {
  snackbarText.value = text
  snackbarColor.value = color
  snackbarOpen.value = true
}

const formatReceiptSum = (value?: any) => formatSum(value)
const formatReceiptDate = (value?: string | null) => formatDate(value)
const formatSpendingSum = (value?: any) => formatSum(value)
const formatSpendingDate = (value?: string | null) => formatDate(value)
const receiptAmount = (value?: any) => {
  if (value && typeof value === 'object' && 'amount' in value) {
    const raw = (value as any).amount
    const parsed = Number(raw)
    return Number.isFinite(parsed) ? parsed : 0
  }
  const parsed = Number(value ?? 0)
  return Number.isFinite(parsed) ? parsed : 0
}
const spendingAmount = (value?: any) => {
  if (value && typeof value === 'object' && 'amount' in value) {
    const raw = (value as any).amount
    const parsed = Number(raw)
    return Number.isFinite(parsed) ? parsed : 0
  }
  const parsed = Number(value ?? 0)
  return Number.isFinite(parsed) ? parsed : 0
}

type SpendingRow = SpendingDraft

const spendingsRows = computed<SpendingRow[]>(() => spendingsDraft.value as SpendingRow[])
const receiptsTotal = computed(() =>
  receipts.value.reduce((sum, row) => sum + receiptAmount(row.sum), 0),
)
const spendingsTotal = computed(() =>
  spendingsDraft.value.reduce((sum, row) => sum + spendingAmount(row.sum), 0),
)
const payrollTotal = computed(() =>
  payrollRows.value.reduce((sum, row) => sum + (Number(row.amount ?? 0) || 0), 0),
)
const planMinusFact = computed(() => analysisTotals.value.planned - analysisTotals.value.actual)
const plannedProfit = computed(() => analysisTotals.value.client - analysisTotals.value.planned)
const plannedMargin = computed(() => {
  const base = analysisTotals.value.client
  if (!base) return 0
  return (plannedProfit.value / base) * 100
})
const actualProfit = computed(() => receiptsTotal.value - analysisTotals.value.actual)
const actualMargin = computed(() => {
  const base = analysisTotals.value.actual
  if (!base) return 0
  return (actualProfit.value / base) * 100
})

const spendingItemsForFund = (fondId?: number | null) => {
  if (!fondId) return dictionaries.spendingItems
  return dictionaries.spendingItems.filter(item => String(item.fond_id ?? '') === String(fondId))
}

const hasNewSpendings = computed(() => spendingsDraft.value.some(row => row.isNew))

const handleSpendingFundChange = (row: SpendingDraft, nextId: number | null) => {
  row.fond_id = nextId ? Number(nextId) : null
  if (!row.spending_item_id) return
  const items = spendingItemsForFund(row.fond_id)
  const exists = items.some(item => String(item.id) === String(row.spending_item_id))
  if (!exists) row.spending_item_id = null
}

const handleSpendingItemChange = (row: SpendingDraft, nextId: number | null) => {
  row.spending_item_id = nextId ? Number(nextId) : null
}

const normalizeFinanceError = (message?: string | null) => {
  if (!message) return null
  if (message === 'Insufficient funds') return 'Недостаточно средств в кассе.'
  return message
}

const currentUserLabel = computed(() => {
  const user = userData.value
  return user?.fullName ?? user?.name ?? user?.email ?? user?.username ?? '-'
})

const parseFileName = (contentDisposition?: string | null) => {
  if (!contentDisposition) return null
  const match = /filename\\*?=(?:UTF-8'')?\"?([^\";]+)\"?/i.exec(contentDisposition)
  if (!match) return null
  return decodeURIComponent(match[1])
}

const statusColor = computed(() => contract.value?.status?.color ?? '#94a3b8')
const canRecalcPayroll = computed(() => {
  const status = contract.value?.status
  if (!status) return false
  const code = String(status.code ?? '').toUpperCase()
  if (['COMPLETED', 'DONE', 'FINISHED', 'DONE_WORK', 'DONE_MONTAGE'].includes(code)) return true
  const name = String(status.name ?? '').toLowerCase()
  return name.includes('выполн')
})

const detailRows = computed(() => [
  {
    key: 'counterparty',
    label: 'Контрагент',
    value: contract.value?.counterparty?.name ?? '-',
  },
  {
    key: 'contract_date',
    label: 'Дата договора',
    value: formatDate(contract.value?.contract_date ?? null),
    editable: true,
    field: 'contract_date',
    type: 'date',
  },
  {
    key: 'address',
    label: 'Адрес',
    value: contract.value?.address ?? '-',
    editable: true,
    field: 'address',
    type: 'text',
  },
  {
    key: 'total_amount',
    label: 'Сумма',
    value: formatMoney(contract.value?.total_amount ?? null),
    editable: true,
    field: 'total_amount',
    type: 'number',
  },
  {
    key: 'paid_amount',
    label: 'Оплачено',
    value: formatMoney(contract.value?.paid_amount ?? null),
  },
  {
    key: 'debt',
    label: 'Долг',
    value: formatMoney(contract.value?.debt ?? null),
  },
  {
    key: 'work_start_date',
    label: 'Начало монтажа',
    value: formatDate(contract.value?.work_start_date ?? null),
    editable: true,
    field: 'work_start_date',
    type: 'date',
  },
  {
    key: 'work_end_date',
    label: 'Конец монтажа',
    value: formatDate(contract.value?.work_end_date ?? null),
    editable: true,
    field: 'work_end_date',
    type: 'date',
  },
  {
    key: 'sale_type',
    label: 'Тип продажи',
    value: contract.value?.sale_type?.name ?? '-',
    editable: true,
    field: 'sale_type_id',
    type: 'select',
  },
  {
    key: 'manager',
    label: 'Менеджер',
    value: contract.value?.manager?.name ?? '-',
  },
  {
    key: 'measurer',
    label: 'Замерщик',
    value: contract.value?.measurer?.name ?? '-',
  },
])

const clientDetails = computed(() => {
  const counterparty = contract.value?.counterparty
  if (!counterparty) return []
  const type = String(counterparty.type ?? '').toLowerCase()
  const isCompany = type === 'company' || type === 'legal'

  if (!isCompany) {
    const individual = counterparty.individual ?? {}
    const passportSeries = individual.passport_series ?? ''
    const passportNumber = individual.passport_number ?? ''
    const passportLabel = [passportSeries, passportNumber].filter(Boolean).join(' ')
    return [
      { label: 'Фамилия', value: individual.last_name ?? '-' },
      { label: 'Имя', value: individual.first_name ?? '-' },
      { label: 'Отчество', value: individual.patronymic ?? '-' },
      { label: 'Телефон', value: counterparty.phone ?? '-' },
      { label: 'Email', value: counterparty.email ?? '-' },
      { label: 'Серия/номер паспорта', value: passportLabel || '-' },
      { label: 'Кем выдан', value: individual.passport_whom ?? '-' },
      { label: 'Когда выдан', value: formatDate(individual.issued_at ?? null) },
      { label: 'Адрес регистрации', value: individual.passport_address ?? '-' },
    ]
  }

  const company = counterparty.company ?? {}
  const innKpp = [company.inn, company.kpp].filter(Boolean).join(' / ')
  return [
    { label: 'Название', value: company.legal_name ?? counterparty.name ?? '-' },
    { label: 'Короткое название', value: company.short_name ?? '-' },
    { label: 'ИНН / КПП', value: innKpp || '-' },
    { label: 'Физ. адрес', value: company.postal_address ?? '-' },
    { label: 'Юр. адрес', value: company.legal_address ?? '-' },
    { label: 'ФИО директора', value: company.director_name ?? '-' },
    { label: 'Р/с', value: company.account_number ?? '-' },
    { label: 'Название банка', value: company.bank_name ?? '-' },
    { label: 'БИК банка', value: company.bik ?? '-' },
    { label: 'Телефон', value: counterparty.phone ?? '-' },
    { label: 'Email', value: counterparty.email ?? '-' },
  ]
})

const loadContract = async () => {
  if (!contractId.value) return
  loading.value = true
  errorMessage.value = ''
  try {
    const response: any = await $api(`contracts/${contractId.value}`)
    contract.value = response?.data ?? null
    syncEditForm()
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось загрузить договор.'
  } finally {
    loading.value = false
  }
}

const syncEditForm = () => {
  editForm.contract_date = toDateInput(contract.value?.contract_date ?? null)
  editForm.total_amount = contract.value?.total_amount ?? null
  editForm.city_id = contract.value?.city_id ?? null
  editForm.sale_type_id = contract.value?.sale_type_id ?? null
  editForm.address = contract.value?.address ?? ''
  editForm.work_start_date = toDateInput(contract.value?.work_start_date ?? null)
  editForm.work_end_date = toDateInput(contract.value?.work_end_date ?? null)
}

const startEdit = () => {
  if (!canEditContract.value) return
  syncEditForm()
  editError.value = ''
  editMode.value = true
}

const cancelEdit = () => {
  editMode.value = false
  editError.value = ''
  syncEditForm()
}

const saveEdit = async () => {
  if (!canEditContract.value) return
  if (!contractId.value) return
  editSaving.value = true
  editError.value = ''
  try {
    const response: any = await $api(`contracts/${contractId.value}`, {
      method: 'PATCH',
      body: {
        contract_date: editForm.contract_date || null,
        total_amount: editForm.total_amount,
        city_id: editForm.city_id,
        sale_type_id: editForm.sale_type_id,
        address: editForm.address || null,
        work_start_date: editForm.work_start_date || null,
        work_end_date: editForm.work_end_date || null,
      },
    })
    contract.value = response?.data ?? contract.value
    editMode.value = false
    syncEditForm()
    await loadAnalysis()
    showSnackbar('Данные договора обновлены.', 'success')
  } catch (error: any) {
    editError.value = error?.response?.data?.message ?? 'Не удалось сохранить изменения.'
    showSnackbar('Не удалось сохранить изменения.', 'error')
  } finally {
    editSaving.value = false
  }
}

const loadHistory = async () => {
  if (!contractId.value) return
  historyLoading.value = true
  historyError.value = ''
  try {
    const response: any = await $api(`contracts/${contractId.value}/history`)
    history.value = response?.data ?? []
  } catch (error: any) {
    historyError.value = error?.response?.data?.message ?? 'Не удалось загрузить историю.'
  } finally {
    historyLoading.value = false
  }
}

const loadDocuments = async () => {
  if (!contractId.value) return
  documentsLoading.value = true
  documentsError.value = ''
  try {
    const response: any = await $api(`contracts/${contractId.value}/documents`)
    documents.value = response?.data ?? []
  } catch (error: any) {
    documentsError.value = error?.response?.data?.message ?? 'Не удалось загрузить документы.'
  } finally {
    documentsLoading.value = false
  }
}

const loadReceipts = async () => {
  if (!canViewFinance.value) {
    receipts.value = []
    return
  }
  if (!contractId.value) return
  receiptsLoading.value = true
  receiptsError.value = ''
  try {
    const response: any = await $api('finance/receipts', {
      query: {
        contract_id: contractId.value,
        per_page: 200,
        include: 'cashbox,creator,fund,item',
      },
    })
    receipts.value = response?.data ?? []
  } catch (error: any) {
    receiptsError.value = error?.response?.data?.message ?? 'Не удалось загрузить оплаты.'
  } finally {
    receiptsLoading.value = false
  }
}

const loadMarginSettings = async () => {
  try {
    const response: any = await $api('settings/margin')
    const data = response?.data as MarginSettings | undefined
    if (data) {
      marginSettings.value = {
        red_max: Number(data.red_max ?? 10),
        orange_max: Number(data.orange_max ?? 20),
      }
    }
  } catch (error) {
    marginSettings.value = { red_max: 10, orange_max: 20 }
  }
}

const loadSpendings = async () => {
  if (!canViewFinance.value) {
    spendings.value = []
    spendingsDraft.value = []
    return
  }
  if (!contractId.value) return
  spendingsLoading.value = true
  spendingsError.value = ''
  try {
    const response: any = await $api('finance/spendings', {
      query: {
        contract_id: contractId.value,
        per_page: 200,
        include: 'cashbox,creator,fund,item',
      },
    })
    const list = response?.data ?? []
    spendings.value = list
    spendingsDraft.value = list.map((row: SpendingDraft) => ({
      ...row,
      isNew: false,
      isDirty: false,
      payment_method_id: null,
    }))
  } catch (error: any) {
    spendingsError.value = error?.response?.data?.message ?? 'Не удалось загрузить расходы.'
  } finally {
    spendingsLoading.value = false
  }
}

const loadAnalysis = async () => {
  if (!contractId.value) return
  analysisLoading.value = true
  analysisError.value = ''
  try {
    const response: any = await $api(`contracts/${contractId.value}/analysis`)
    const payload = (response?.data ?? {}) as ContractAnalysisResponse
    analysisRows.value = payload.rows ?? []
    analysisTotals.value = payload.totals ?? { client: 0, planned: 0, actual: 0, delta: 0 }
  } catch (error: any) {
    analysisError.value = error?.response?.data?.message ?? 'Не удалось загрузить анализ.'
  } finally {
    analysisLoading.value = false
  }
}

const loadPayroll = async () => {
  if (!canViewPayroll.value) {
    payrollRows.value = []
    return
  }
  if (!contractId.value) return
  payrollLoading.value = true
  payrollError.value = ''
  try {
    const response: any = await $api(`contracts/${contractId.value}/payroll`)
    payrollRows.value = response?.data ?? []
  } catch (error: any) {
    payrollError.value = error?.response?.data?.message ?? 'Не удалось загрузить начисления.'
  } finally {
    payrollLoading.value = false
  }
}

const saveManualPayroll = async () => {
  if (!canCreatePayroll.value) return
  if (!contractId.value) return
  const amount = Number(payrollAmount.value ?? 0)
  if (!amount || Number.isNaN(amount)) {
    payrollError.value = 'Введите сумму.'
    showSnackbar('Введите сумму.', 'error')
    return
  }

  payrollSaving.value = true
  payrollError.value = ''
  try {
    await $api(`contracts/${contractId.value}/payroll/manual`, {
      method: 'POST',
      body: {
        type: payrollType.value,
        amount,
        comment: payrollComment.value || null,
      },
    })
    payrollAmount.value = null
    payrollComment.value = ''
    await loadPayroll()
    showSnackbar('Начисление добавлено.', 'success')
  } catch (error: any) {
    payrollError.value = error?.response?.data?.message ?? 'Не удалось добавить начисление.'
    showSnackbar(payrollError.value, 'error')
  } finally {
    payrollSaving.value = false
  }
}

const recalcPayroll = async () => {
  if (!canEditPayroll.value) return
  if (!contractId.value) return
  payrollRecalcLoading.value = true
  payrollError.value = ''
  try {
    await $api(`contracts/${contractId.value}/payroll/recalculate`, { method: 'POST' })
    await loadPayroll()
    showSnackbar('Пересчитано.', 'success')
  } catch (error: any) {
    const status = error?.response?.status
    const message = error?.response?.data?.message
      ?? (status === 409 ? 'Пересчет доступен после статуса "Выполнен".' : null)
      ?? 'Не удалось пересчитать начисления.'
    payrollError.value = message
    showSnackbar(message, 'error')
  } finally {
    payrollRecalcLoading.value = false
  }
}

const ensureSpendingLookups = async () => {
  await Promise.all([
    dictionaries.loadCashBoxes(),
    dictionaries.loadPaymentMethods(),
    dictionaries.loadSpendingFunds(),
    dictionaries.loadSpendingItems(),
  ])
}

const addSpendingRow = async () => {
  if (!canCreateFinance.value) return
  await ensureSpendingLookups()
  const paymentMethodId = dictionaries.paymentMethods[0]?.id ?? null
  const draft: SpendingRow = {
    id: spendingTempId--,
    cashbox_id: null,
    payment_method_id: paymentMethodId ? Number(paymentMethodId) : null,
    fond_id: null,
    spending_item_id: null,
    sum: null,
    payment_date: new Date().toISOString().slice(0, 10),
    description: '',
    contract_id: contractId.value ? Number(contractId.value) : undefined,
    counterparty_id: contract.value?.counterparty_id,
    isNew: true,
    isDirty: true,
  }
  spendingsDraft.value.unshift(draft)
}

const saveSpendings = async () => {
  if (!canCreateFinance.value) return
  if (!contractId.value) return
  const newRows = spendingsDraft.value.filter(row => row.isNew)
  if (!newRows.length) return

  spendingsSaving.value = true
  spendingsError.value = ''

  try {
    await ensureSpendingLookups()

    for (const row of newRows) {
      if (!row.cashbox_id || !row.fond_id || !row.spending_item_id || !row.sum || !row.payment_date) {
        spendingsError.value = 'Заполните все обязательные поля в новых строках.'
        showSnackbar('Заполните все обязательные поля.', 'error')
        spendingsSaving.value = false
        return
      }

      const paymentMethodId = row.payment_method_id ?? dictionaries.paymentMethods[0]?.id ?? null
      if (!paymentMethodId) {
        spendingsError.value = 'Не задан способ оплаты.'
        showSnackbar('Не задан способ оплаты.', 'error')
        spendingsSaving.value = false
        return
      }

      await $api('finance/spendings', {
        method: 'POST',
        body: {
          contract_id: Number(contractId.value),
          cashbox_id: row.cashbox_id,
          payment_method_id: paymentMethodId,
          fond_id: row.fond_id,
          spending_item_id: row.spending_item_id,
          sum: row.sum,
          payment_date: normalizeDateValue(row.payment_date),
          description: row.description || null,
          counterparty_id: contract.value?.counterparty_id ?? null,
        },
      })
    }

    await loadSpendings()
    await loadAnalysis()
    showSnackbar('Расходы сохранены.', 'success')
  } catch (error: any) {
    const message = normalizeFinanceError(error?.response?.data?.message ?? error?.data?.message)
    spendingsError.value = message ?? 'Не удалось сохранить расходы.'
    showSnackbar(message ?? 'Не удалось сохранить расходы.', 'error')
  } finally {
    spendingsSaving.value = false
  }
}

const requestDeleteSpending = (row: SpendingDraft) => {
  if (!canDeleteFinance.value) return
  if (row.isNew) {
    spendingsDraft.value = spendingsDraft.value.filter(item => item !== row)
    return
  }
  pendingSpending.value = row
  confirmSpendingDeleteOpen.value = true
}

const deleteSpending = async () => {
  if (!canDeleteFinance.value) return
  const target = pendingSpending.value
  if (!target?.id) return
  deletingSpending.value = true
  spendingsError.value = ''
  try {
    await $api(`finance/spendings/${target.id}`, { method: 'DELETE' })
    showSnackbar('Расход удален.', 'success')
    await loadSpendings()
    await loadAnalysis()
  } catch (error: any) {
    const message = normalizeFinanceError(error?.response?.data?.message ?? error?.data?.message)
    spendingsError.value = message ?? 'Не удалось удалить расход.'
    showSnackbar(message ?? 'Не удалось удалить расход.', 'error')
  } finally {
    deletingSpending.value = false
    confirmSpendingDeleteOpen.value = false
    pendingSpending.value = null
  }
}

const generateDocx = async (documentId?: number | null) => {
  if (!canCreateContractDocs.value) return
  if (!contractId.value) return
  generatingId.value = documentId ?? 0
  documentsError.value = ''
  try {
    const response: any = await $api(`contracts/${contractId.value}/documents`, {
      method: 'POST',
      body: documentId ? { document_id: documentId } : undefined,
    })
    const created = response?.data
    if (created?.id) {
      await loadDocuments()
    }
  } catch (error: any) {
    documentsError.value = error?.response?.data?.message ?? 'Не удалось сформировать документ.'
  } finally {
    generatingId.value = null
  }
}

const downloadDocument = async (doc: ContractDocument) => {
  if (!contractId.value || !doc.id) return
  downloadingId.value = doc.id
  documentsError.value = ''
  try {
    const accessToken = useCookie('accessToken').value
    const baseUrl = import.meta.env.VITE_API_BASE_URL || '/api'
    const url = `${baseUrl}/contracts/${contractId.value}/documents/${doc.id}/download`
    const response = await fetch(url, {
      headers: accessToken ? { Authorization: `Bearer ${accessToken}` } : {},
    })

    if (!response.ok) {
      throw new Error('Download failed')
    }

    const blob = await response.blob()
    const fileName = parseFileName(response.headers.get('content-disposition'))
      ?? doc.file_name
      ?? fileLabel(doc.file_path)
      ?? `contract-${contractId.value}.docx`

    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = fileName
    document.body.appendChild(link)
    link.click()
    URL.revokeObjectURL(link.href)
    link.remove()
  } catch (error: any) {
    documentsError.value = error?.message ?? 'Не удалось скачать документ.'
  } finally {
    downloadingId.value = null
  }
}

const requestDeleteDocument = (doc: ContractDocument) => {
  if (!canDeleteContractDocs.value) return
  if (!doc?.id) return
  pendingDocument.value = doc
  confirmDocumentDeleteOpen.value = true
}

const deleteDocument = async () => {
  if (!canDeleteContractDocs.value) return
  if (!contractId.value || !pendingDocument.value?.id) return
  const documentId = pendingDocument.value.id
  deletingDocumentId.value = documentId
  documentsError.value = ''
  try {
    await $api(`contracts/${contractId.value}/documents/${documentId}`, {
      method: 'DELETE',
    })
    await loadDocuments()
    showSnackbar('Документ удален.', 'success')
  } catch (error: any) {
    documentsError.value = error?.response?.data?.message ?? 'Не удалось удалить документ.'
    showSnackbar('Не удалось удалить документ.', 'error')
  } finally {
    deletingDocumentId.value = null
    confirmDocumentDeleteOpen.value = false
    pendingDocument.value = null
  }
}

const requestDeleteContract = () => {
  if (!canDeleteContract.value) return
  confirmDeleteOpen.value = true
}

const deleteContract = async () => {
  if (!canDeleteContract.value) return
  if (!contractId.value) return
  deleting.value = true
  errorMessage.value = ''
  try {
    await $api(`contracts/${contractId.value}`, { method: 'DELETE' })
    showSnackbar('Договор удален.', 'success')
    await new Promise(resolve => setTimeout(resolve, 300))
    await router.push({ path: '/operations/contracts', query: { toast: 'contract-deleted' } })
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось удалить договор.'
    showSnackbar('Не удалось удалить договор.', 'error')
  } finally {
    deleting.value = false
    confirmDeleteOpen.value = false
  }
}

const goBack = () => router.push({ path: '/operations/contracts' })

watch(contractId, async () => {
  await loadContract()
  await loadHistory()
  await loadDocuments()
  await loadReceipts()
  await loadSpendings()
  await loadAnalysis()
  await loadPayroll()
})

onMounted(async () => {
  await Promise.all([dictionaries.loadSaleTypes(), dictionaries.loadCities()])
  await loadMarginSettings()
  await loadContract()
  await loadHistory()
  await loadDocuments()
  await loadReceipts()
  await loadSpendings()
  await loadAnalysis()
  await loadPayroll()
})
</script>

<template>
  <div class="flex flex-column gap-4">
    <div class="flex flex-wrap align-items-center justify-between gap-3">
      <div class="flex flex-column gap-1">
        <div class="flex flex-wrap align-items-center gap-2">
          <h2 class="text-2xl font-semibold m-0">Договор #{{ contractId }}</h2>
          <Tag
            v-if="contract?.status?.name"
            :value="contract.status.name"
            :style="{ backgroundColor: statusColor, color: '#fff' }"
          />
        </div>
        <div class="text-sm text-muted">
          {{ contract?.title ?? '—' }}
        </div>
      </div>
      <div class="flex flex-wrap align-items-center gap-2">
        <Button
          v-if="canDeleteContract"
          label="Удалить"
          icon="pi pi-trash"
          severity="danger"
          outlined
          :loading="deleting"
          @click="requestDeleteContract"
        />
        <Button
          label="Назад"
          icon="pi pi-arrow-left"
          outlined
          @click="goBack"
        />
      </div>
    </div>

    <div v-if="errorMessage" class="p-3 border-round" style="background: #fee2e2; color: #b91c1c;">
      {{ errorMessage }}
    </div>

    <VTabs v-model="activeTab" class="mb-3">
      <VTab value="card">Карточка</VTab>
      <VTab value="client">Клиент</VTab>
      <VTab value="documents">Документы</VTab>
      <VTab v-if="canViewFinance" value="payments">Оплаты</VTab>
      <VTab v-if="canViewFinance" value="spendings">Расходы</VTab>
      <VTab value="installation">Монтаж</VTab>
      <VTab value="analysis">Анализ</VTab>
      <VTab v-if="canViewPayroll" value="payroll">З/П</VTab>
      <VTab value="history">История</VTab>
    </VTabs>
    <VWindow v-model="activeTab">
      <VWindowItem value="card">
        <Card>
          <template #content>
            <div class="flex justify-end gap-2 mb-3">
              <Button
                v-if="!editMode && canEditContract"
                icon="pi pi-pencil"
                label="Редактировать"
                outlined
                @click="startEdit"
              />
              <template v-else>
                <Button
                  icon="pi pi-check"
                  label="Сохранить"
                  :loading="editSaving"
                  :disabled="!canEditContract"
                  @click="saveEdit"
                />
                <Button
                  icon="pi pi-times"
                  label="Отмена"
                  outlined
                  @click="cancelEdit"
                />
              </template>
            </div>

            <div v-if="editError" class="text-sm mb-3" style="color: #b91c1c;">
              {{ editError }}
            </div>

            <div v-if="loading" class="text-sm text-muted">Загрузка...</div>
            <DataTable
              v-else
              :value="detailRows"
              dataKey="key"
              class="p-datatable-sm"
            >
              <Column field="label" header="Поле" />
              <Column field="value" header="Значение">
                <template #body="{ data: row }">
                  <template v-if="editMode && row.editable">
                    <AppDateTimePicker
                      v-if="row.type === 'date'"
                      :model-value="editForm[row.field]"
                      placeholder="ДД.ММ.ГГГГ"
                      :config="datePickerConfig"
                      hide-details
                      @update:modelValue="updateEditField(row.field, $event)"
                    />
                    <AppSelect
                      v-else-if="row.type === 'select'"
                      :model-value="editForm[row.field]"
                      :items="dictionaries.saleTypes"
                      item-title="name"
                      item-value="id"
                      hide-details
                      @update:modelValue="updateEditField(row.field, $event)"
                    />
                    <VTextField
                      v-else
                      :model-value="editForm[row.field]"
                      :type="row.type === 'number' ? 'number' : 'text'"
                      hide-details
                      @update:modelValue="updateEditField(row.field, $event)"
                    />
                  </template>
                  <span v-else>{{ row.value }}</span>
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>
      </VWindowItem>

          <VWindowItem value="client">
            <Card>
              <template #content>
                <DataTable
                  :value="clientDetails"
                  dataKey="label"
                  class="p-datatable-sm"
                >
                  <Column field="label" header="Поле" />
                  <Column field="value" header="Значение" />
                </DataTable>
              </template>
            </Card>
          </VWindowItem>

      <VWindowItem value="documents">
        <Card>
          <template #content>
                <div v-if="documentsError" class="text-sm" style="color: #b91c1c;">
                  {{ documentsError }}
                </div>
                <div v-if="documentsLoading" class="text-sm text-muted">Загрузка...</div>
                <DataTable
                  v-else
                  :value="documents"
                  dataKey="id"
                  class="p-datatable-sm"
                >
                  <Column field="version" header="Версия" style="inline-size: 10ch;">
                    <template #body="{ data: row }">
                      {{ row.version ?? '-' }}
                    </template>
                  </Column>
                  <Column field="template_name" header="Шаблон">
                    <template #body="{ data: row }">
                      {{ row.template_name ?? '-' }}
                    </template>
                  </Column>
                  <Column field="document_type" header="Тип" style="inline-size: 16ch;">
                    <template #body="{ data: row }">
                      {{ formatDocumentType(row) }}
                    </template>
                  </Column>
                  <Column field="file_path" header="Файл">
                    <template #body="{ data: row }">
                      {{ row.file_name ?? fileLabel(row.file_path) }}
                    </template>
                  </Column>
                  <Column field="file_size" header="Размер" style="inline-size: 12ch;">
                    <template #body="{ data: row }">
                      {{ formatFileSize(row.file_size) }}
                    </template>
                  </Column>
                  <Column field="generated_at" header="Сформирован" style="inline-size: 18ch;">
                    <template #body="{ data: row }">
                      {{ formatDateTime(row.generated_at ?? row.created_at) }}
                    </template>
                  </Column>
                  <Column header="Действия" style="inline-size: 24ch;">
                    <template #body="{ data: row }">
                      <div class="flex flex-wrap gap-2">
                        <Button
                          icon="pi pi-file"
                          label="Сформировать"
                          text
                          :loading="generatingId === row.id"
                          :disabled="generatingId === row.id || !canCreateContractDocs"
                          @click="generateDocx(row.id)"
                        />
                        <Button
                          icon="pi pi-download"
                          label="Скачать"
                          text
                          :loading="downloadingId === row.id"
                          :disabled="downloadingId === row.id || !row.file_path"
                          @click="downloadDocument(row)"
                        />
                        <Button
                          v-if="canDeleteContractDocs"
                          icon="pi pi-trash"
                          label="Удалить"
                          text
                          severity="danger"
                          :loading="deletingDocumentId === row.id"
                          :disabled="deletingDocumentId === row.id"
                          @click="requestDeleteDocument(row)"
                        />
                      </div>
                    </template>
                  </Column>
                  <Column field="is_current" header="Актуальный" style="inline-size: 12ch;">
                    <template #body="{ data: row }">
                      <Tag v-if="row.is_current" value="Да" severity="success" />
                      <span v-else>-</span>
                    </template>
                  </Column>
                  <template #empty>
                    <div class="text-center py-6 text-muted">Документы не сформированы.</div>
                  </template>
                </DataTable>
              </template>
        </Card>
      </VWindowItem>

      <VWindowItem v-if="canViewFinance" value="payments">
        <Card>
          <template #content>
            <div v-if="receiptsError" class="text-sm" style="color: #b91c1c;">
              {{ receiptsError }}
            </div>
            <div v-if="receiptsLoading" class="text-sm text-muted">Загрузка...</div>
            <DataTable
              v-else
              :value="receipts"
              dataKey="id"
              class="p-datatable-sm"
            >
              <Column field="id" header="№" style="inline-size: 6ch;">
                <template #body="{ data: row }">
                  {{ row.id ?? '-' }}
                </template>
              </Column>
              <Column field="transaction_id" header="Код транзакции" style="inline-size: 12ch;">
                <template #body="{ data: row }">
                  {{ row.transaction_id ?? '-' }}
                </template>
              </Column>
              <Column field="id" header="Код прихода" style="inline-size: 10ch;">
                <template #body="{ data: row }">
                  {{ row.id ?? '-' }}
                </template>
              </Column>
              <Column field="payment_date" header="Дата" style="inline-size: 12ch;">
                <template #body="{ data: row }">
                  {{ formatReceiptDate(row.payment_date) }}
                </template>
              </Column>
              <Column field="sum" header="Сумма" style="inline-size: 12ch;">
                <template #body="{ data: row }">
                  {{ formatReceiptSum(row.sum) }}
                </template>
              </Column>
              <Column field="cashbox" header="Касса">
                <template #body="{ data: row }">
                  <CashboxCell :cashbox="row.cashbox" size="sm" />
                </template>
              </Column>
              <Column field="creator" header="Кто добавил">
                <template #body="{ data: row }">
                  {{ row.creator?.name ?? row.creator?.email ?? '-' }}
                </template>
              </Column>
              <template #empty>
                <div class="text-center py-6 text-muted">Оплат нет.</div>
              </template>
            </DataTable>
            <Divider />
            <div class="flex justify-end text-sm font-semibold">
              Сумма: {{ formatMoney(receiptsTotal) }}
            </div>
          </template>
        </Card>
      </VWindowItem>

      <VWindowItem v-if="canViewFinance" value="spendings">
        <Card>
          <template #content>
            <div v-if="spendingsError" class="text-sm" style="color: #b91c1c;">
              {{ spendingsError }}
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
              <div class="flex flex-wrap items-center gap-2">
                <VBtn
                  color="primary"
                  prepend-icon="tabler-plus"
                  :disabled="spendingsSaving || !canCreateFinance"
                  @click="addSpendingRow"
                >
                  Добавить расход
                </VBtn>
                <VBtn
                  color="success"
                  prepend-icon="tabler-device-floppy"
                  :loading="spendingsSaving"
                  :disabled="!hasNewSpendings || !canCreateFinance"
                  @click="saveSpendings"
                >
                  Сохранить
                </VBtn>
              </div>
            </div>
            <div v-if="spendingsLoading" class="text-sm text-muted">Загрузка...</div>
            <DataTable
              v-else
              :value="spendingsRows"
              dataKey="id"
              class="p-datatable-sm"
            >
              <Column field="payment_date" header="Дата" style="inline-size: 12ch;">
                <template #body="{ data: row }">
                  <AppDateTimePicker
                    v-if="row.isNew"
                    v-model="row.payment_date"
                    placeholder="ДД.ММ.ГГГГ"
                    :config="datePickerConfig"
                    class="spendings-input"
                    hide-details
                  />
                  <span v-else>{{ formatSpendingDate(row.payment_date) }}</span>
                </template>
              </Column>
              <Column field="sum" header="Сумма" style="inline-size: 12ch;">
                <template #body="{ data: row }">
                  <VTextField
                    v-if="row.isNew"
                    v-model.number="row.sum"
                    type="number"
                    density="compact"
                    class="spendings-input"
                    hide-details
                  />
                  <span v-else>{{ formatSpendingSum(row.sum) }}</span>
                </template>
              </Column>
              <Column field="fund" header="Фонд" style="inline-size: 16ch;">
                <template #body="{ data: row }">
                  <AppSelect
                    v-if="row.isNew"
                    :model-value="row.fond_id"
                    :items="dictionaries.spendingFunds"
                    item-title="name"
                    item-value="id"
                    density="compact"
                    class="spendings-input"
                    hide-details
                    @update:modelValue="handleSpendingFundChange(row, $event)"
                  />
                  <span v-else>{{ row.fund?.name ?? '-' }}</span>
                </template>
              </Column>
              <Column field="item" header="Статья">
                <template #body="{ data: row }">
                  <AppSelect
                    v-if="row.isNew"
                    :model-value="row.spending_item_id"
                    :items="spendingItemsForFund(row.fond_id)"
                    item-title="name"
                    item-value="id"
                    density="compact"
                    class="spendings-input"
                    hide-details
                    @update:modelValue="handleSpendingItemChange(row, $event)"
                  />
                  <span v-else>{{ row.item?.name ?? '-' }}</span>
                </template>
              </Column>
              <Column field="description" header="Комментарий">
                <template #body="{ data: row }">
                  <VTextField
                    v-if="row.isNew"
                    v-model="row.description"
                    density="compact"
                    class="spendings-input"
                    hide-details
                  />
                  <span v-else>{{ row.description ?? '-' }}</span>
                </template>
              </Column>
              <Column field="cashbox" header="Касса">
                <template #body="{ data: row }">
                  <AppSelect
                    v-if="row.isNew"
                    :model-value="row.cashbox_id"
                    :items="dictionaries.cashBoxes"
                    item-title="name"
                    item-value="id"
                    density="compact"
                    class="spendings-input"
                    hide-details
                    @update:modelValue="row.cashbox_id = $event ? Number($event) : null"
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
                  <CashboxCell v-else :cashbox="row.cashbox" size="sm" />
                </template>
              </Column>
              <Column field="creator" header="Пользователь">
                <template #body="{ data: row }">
                  <span v-if="row.isNew">{{ currentUserLabel }}</span>
                  <span v-else>{{ row.creator?.name ?? row.creator?.email ?? '-' }}</span>
                </template>
              </Column>
              <Column v-if="canDeleteFinance" header="" style="inline-size: 6ch;">
                <template #body="{ data: row }">
                  <Button
                    icon="pi pi-trash"
                    text
                    severity="danger"
                    @click="requestDeleteSpending(row)"
                  />
                </template>
              </Column>
              <template #empty>
                <div class="text-center py-6 text-muted">Расходов нет.</div>
              </template>
            </DataTable>
            <Divider />
            <div class="flex justify-end text-sm font-semibold">
              Сумма: {{ formatMoney(spendingsTotal) }}
            </div>
          </template>
        </Card>
      </VWindowItem>

      <VWindowItem value="installation">
        <Card>
          <template #content>
            <div class="text-sm text-muted">Раздел в разработке.</div>
          </template>
        </Card>
          </VWindowItem>

          <VWindowItem value="analysis">
            <Card>
              <template #content>
                <div v-if="analysisError" class="text-sm" style="color: #b91c1c;">
                  {{ analysisError }}
                </div>
                <div v-if="analysisLoading" class="text-sm text-muted">Загрузка...</div>
                <DataTable
                  v-else
                  :value="analysisRows"
                  dataKey="category"
                  class="p-datatable-sm"
                >
                  <Column field="category" header="Статья">
                    <template #body="{ data: row }">
                      {{ row.category }}
                    </template>
                    <template #footer>
                      <span class="font-semibold">Итого</span>
                    </template>
                  </Column>
                  <Column field="client" header="Цена клиента" style="inline-size: 16ch;">
                    <template #body="{ data: row }">
                      {{ formatMoney(row.client) }}
                    </template>
                    <template #footer>
                      <div class="flex justify-end font-semibold">
                        {{ formatMoney(analysisTotals.client) }}
                      </div>
                    </template>
                  </Column>
                  <Column field="planned" header="План" style="inline-size: 16ch;">
                    <template #body="{ data: row }">
                      {{ formatMoney(row.planned) }}
                    </template>
                    <template #footer>
                      <div class="flex justify-end font-semibold">
                        {{ formatMoney(analysisTotals.planned) }}
                      </div>
                    </template>
                  </Column>
                  <Column field="actual" header="Факт" style="inline-size: 16ch;">
                    <template #body="{ data: row }">
                      {{ formatMoney(row.actual) }}
                    </template>
                    <template #footer>
                      <div class="flex justify-end font-semibold">
                        {{ formatMoney(analysisTotals.actual) }}
                      </div>
                    </template>
                  </Column>
                  <Column field="delta" header="Отклонение" style="inline-size: 16ch;">
                    <template #body="{ data: row }">
                      {{ formatMoney(row.delta) }}
                    </template>
                    <template #footer>
                      <div class="flex justify-end font-semibold">
                        {{ formatMoney(analysisTotals.delta) }}
                      </div>
                    </template>
                  </Column>
                  <template #empty>
                    <div class="text-center py-6 text-muted">Нет данных.</div>
                  </template>
                </DataTable>
                <Divider />
                <div class="flex flex-wrap items-center justify-end gap-4 text-sm font-semibold">
                  <div>Приходы: {{ formatMoney(receiptsTotal) }}</div>
                  <div>Расходы: {{ formatMoney(analysisTotals.actual) }}</div>
                </div>
                <div class="flex flex-column items-end gap-1 text-sm font-semibold">
                  <div :style="{ color: planMinusFactColor }">
                    План - факт: {{ formatMoney(planMinusFact) }}
                  </div>
                  <div>Плановая валовая прибыль: {{ formatMoney(plannedProfit) }}</div>
                  <div>
                    Плановая валовая маржа:
                    <span :style="{ color: marginColor(plannedMargin) }">
                      {{ formatPercent(plannedMargin) }}
                    </span>
                  </div>
                  <div>Валовая прибыль: {{ formatMoney(actualProfit) }}</div>
                  <div>
                    Валовая маржа:
                    <span :style="{ color: marginColor(actualMargin) }">
                      {{ formatPercent(actualMargin) }}
                    </span>
                  </div>
                </div>
              </template>
            </Card>
          </VWindowItem>

          <VWindowItem v-if="canViewPayroll" value="payroll">
            <Card>
              <template #content>
                <div v-if="payrollError" class="text-sm" style="color: #b91c1c;">
                  {{ payrollError }}
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                  <div class="flex flex-wrap items-center gap-2">
                    <Button
                      v-if="canEditPayroll"
                      label="Пересчитать"
                      icon="pi pi-refresh"
                      outlined
                      :loading="payrollRecalcLoading"
                      :disabled="!canRecalcPayroll || payrollRecalcLoading"
                      :title="!canRecalcPayroll ? 'Доступно после статуса Выполнен' : ''"
                      @click="recalcPayroll"
                    />
                  </div>
                </div>
                <div v-if="payrollLoading" class="text-sm text-muted">Загрузка...</div>
                <DataTable
                  v-else
                  :value="payrollRows"
                  dataKey="id"
                  class="p-datatable-sm"
                >
                  <Column field="created_at" header="Дата" style="inline-size: 18ch;">
                    <template #body="{ data: row }">
                      {{ formatDateTime(row.created_at) }}
                    </template>
                  </Column>
                  <Column field="type" header="Тип" style="inline-size: 18ch;">
                    <template #body="{ data: row }">
                      {{ formatPayrollType(row) }}
                    </template>
                  </Column>
                  <Column field="document_type" header="Документ" style="inline-size: 16ch;">
                    <template #body="{ data: row }">
                      {{ formatPayrollDocumentType(row) }}
                    </template>
                  </Column>
                  <Column field="base_amount" header="База" style="inline-size: 14ch;">
                    <template #body="{ data: row }">
                      {{ formatMoney(row.base_amount) }}
                    </template>
                  </Column>
                  <Column field="percent" header="%" style="inline-size: 10ch;">
                    <template #body="{ data: row }">
                      {{ formatPercent(row.percent) }}
                    </template>
                  </Column>
                  <Column field="amount" header="Сумма" style="inline-size: 14ch;">
                    <template #body="{ data: row }">
                      {{ formatMoney(row.amount) }}
                    </template>
                  </Column>
                  <Column field="status" header="Статус" style="inline-size: 12ch;">
                    <template #body="{ data: row }">
                      <Tag
                        :value="formatPayrollStatus(row)"
                        :severity="row.status === 'paid' ? 'success' : row.status === 'cancelled' ? 'danger' : 'secondary'"
                      />
                    </template>
                  </Column>
                  <Column field="source" header="Источник" style="inline-size: 12ch;">
                    <template #body="{ data: row }">
                      {{ formatPayrollSource(row) }}
                    </template>
                  </Column>
                  <Column field="comment" header="Комментарий">
                    <template #body="{ data: row }">
                      {{ row.comment ?? '-' }}
                    </template>
                  </Column>
                  <template #empty>
                    <div class="text-center py-6 text-muted">Начислений нет.</div>
                  </template>
                </DataTable>
                <Divider />
                <div class="flex justify-end text-sm font-semibold">
                  Сумма: {{ formatMoney(payrollTotal) }}
                </div>
                <div v-if="canCreatePayroll" class="mt-4">
                  <div class="text-sm font-semibold mb-2">Ручное начисление</div>
                  <div class="flex flex-wrap items-end gap-2">
                    <AppSelect
                      :model-value="payrollType"
                      :items="PAYROLL_TYPE_OPTIONS"
                      item-title="title"
                      item-value="value"
                      style="min-width: 180px;"
                      hide-details
                      @update:modelValue="payrollType = $event"
                    />
                    <VTextField
                      v-model.number="payrollAmount"
                      type="number"
                      label="Сумма"
                      hide-details
                      style="min-width: 160px;"
                    />
                    <VTextField
                      v-model="payrollComment"
                      label="Комментарий"
                      hide-details
                      style="min-width: 260px;"
                    />
                    <Button
                      label="Добавить"
                      icon="pi pi-plus"
                      :loading="payrollSaving"
                      @click="saveManualPayroll"
                    />
                  </div>
                </div>
              </template>
            </Card>
          </VWindowItem>

          <VWindowItem value="history">
            <Card>
              <template #content>
                <div v-if="historyError" class="text-sm" style="color: #b91c1c;">
                  {{ historyError }}
                </div>
                <div v-if="historyLoading" class="text-sm text-muted">Загрузка...</div>
                <DataTable
                  v-else
                  :value="history"
                  dataKey="id"
                  class="p-datatable-sm"
                >
                  <Column field="created_at" header="Дата" style="inline-size: 16ch;">
                    <template #body="{ data: row }">
                      {{ formatDateTime(row.created_at) }}
                    </template>
                  </Column>
                  <Column field="title" header="Событие">
                    <template #body="{ data: row }">
                      {{ row.title ?? '-' }}
                    </template>
                  </Column>
                  <Column field="user" header="Кто">
                    <template #body="{ data: row }">
                      {{ row.user?.name ?? row.user?.email ?? '-' }}
                    </template>
                  </Column>
                  <template #empty>
                    <div class="text-center py-6 text-muted">История пуста.</div>
                  </template>
                </DataTable>
                <Divider />
                <div class="flex justify-end text-sm text-muted">История</div>
              </template>
            </Card>
          </VWindowItem>
    </VWindow>

    <VDialog v-model="confirmDeleteOpen" max-width="420">
      <VCard>
        <VCardTitle>Удалить договор?</VCardTitle>
        <VCardText>Договор и связанные документы будут удалены без восстановления.</VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn variant="text" @click="confirmDeleteOpen = false">Отмена</VBtn>
          <VBtn color="error" :loading="deleting" @click="deleteContract">Удалить</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog v-model="confirmDocumentDeleteOpen" max-width="420">
      <VCard>
        <VCardTitle>Удалить документ?</VCardTitle>
        <VCardText>
          Документ {{ pendingDocument?.template_name ?? pendingDocument?.file_name ?? 'без названия' }} будет удален.
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn variant="text" @click="confirmDocumentDeleteOpen = false">Отмена</VBtn>
          <VBtn
            color="error"
            :loading="deletingDocumentId === pendingDocument?.id"
            @click="deleteDocument"
          >
            Удалить
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog v-model="confirmSpendingDeleteOpen" max-width="420">
      <VCard>
        <VCardTitle>Удалить расход?</VCardTitle>
        <VCardText>Расход будет удален без восстановления.</VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn variant="text" @click="confirmSpendingDeleteOpen = false">Отмена</VBtn>
          <VBtn color="error" :loading="deletingSpending" @click="deleteSpending">Удалить</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VSnackbar v-model="snackbarOpen" :color="snackbarColor" :timeout="2500">
      {{ snackbarText }}
    </VSnackbar>
  </div>
</template>

<style scoped>
.spendings-input :deep(.v-field__input),
.spendings-input :deep(.v-select__selection),
.spendings-input :deep(.v-field__input input),
.spendings-input :deep(input),
.spendings-input :deep(textarea) {
  color: rgb(var(--v-theme-on-surface));
}

.spendings-input :deep(.v-field__input input::placeholder),
.spendings-input :deep(input::placeholder),
.spendings-input :deep(textarea::placeholder) {
  color: rgba(var(--v-theme-on-surface), 0.5);
}
</style>
