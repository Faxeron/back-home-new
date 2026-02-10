<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useTheme } from 'vuetify'
import { hexToRgb } from '@layouts/utils'
import { $api } from '@/utils/api'
import { useDictionariesStore } from '@/stores/dictionaries'
import { formatSum } from '@/utils/formatters/finance'
import AppSelect from '@/@core/components/app-form-elements/AppSelect.vue'

type CashflowMonthlyPoint = {
  month: string
  opening_balance: number
  inflow_total: number
  outflow_total: number
  net_cashflow: number
  closing_balance: number
}

type PnlMonthlyPoint = {
  month: string
  revenue_operating: number
  expense_operating: number
  operating_profit: number
  finance_in: number
  finance_out: number
  net_result: number
}

type DebtsSummaryPoint = {
  type: 'AR' | 'AP' | string
  type_name: string
  records: number
  total_debt: number
  total_amount: number
}

const dictionaries = useDictionariesStore()
const theme = useTheme()

const filters = reactive({
  company_id: null as number | null,
  from_month: null as string | null,
  to_month: null as string | null,
})

const loadingCashflow = ref(false)
const cashflowError = ref('')
const cashflowPoints = ref<CashflowMonthlyPoint[]>([])

const loadingPnl = ref(false)
const pnlError = ref('')
const pnlPoints = ref<PnlMonthlyPoint[]>([])
const pnlTotals = ref<{
  revenue_operating: number
  expense_operating: number
  operating_profit: number
  finance_in: number
  finance_out: number
} | null>(null)

const loadingDebts = ref(false)
const debtsError = ref('')
const debtsSummary = ref<DebtsSummaryPoint[]>([])

const rebuilding = ref(false)
const rebuildError = ref('')
const rebuildResult = ref<any>(null)

const monthRangeLabel = computed(() => {
  if (!cashflowPoints.value.length)
    return 'Нет данных'

  return `${cashflowPoints.value[0].month} — ${cashflowPoints.value[cashflowPoints.value.length - 1].month}`
})

const currentCompanyName = computed(() => {
  const id = filters.company_id
  if (!id)
    return ''
  const c = dictionaries.companies.find(cmp => Number(cmp.id) === Number(id))
  return c?.name ?? ''
})

const loadCashflow = async () => {
  loadingCashflow.value = true
  cashflowError.value = ''
  try {
    const params: Record<string, any> = {}
    if (filters.company_id)
      params.company_id = filters.company_id
    if (filters.from_month)
      params.from_month = filters.from_month
    if (filters.to_month)
      params.to_month = filters.to_month

    const response: any = await $api('reports/cashflow/monthly-summary', { params })
    const data = response?.data ?? response
    cashflowPoints.value = Array.isArray(data?.data) ? data.data : []
  } catch (error: any) {
    cashflowError.value =
      error?.response?.data?.message ??
      error?.data?.message ??
      'Не удалось загрузить сводку денежного потока.'
    cashflowPoints.value = []
  } finally {
    loadingCashflow.value = false
  }
}

const loadPnl = async () => {
  loadingPnl.value = true
  pnlError.value = ''
  try {
    const params: Record<string, any> = {}
    if (filters.company_id)
      params.company_id = filters.company_id
    if (filters.from_month)
      params.from_month = filters.from_month
    if (filters.to_month)
      params.to_month = filters.to_month

    const response: any = await $api('reports/pnl/monthly', { params })
    const data = response?.data ?? response
    pnlPoints.value = Array.isArray(data?.data) ? data.data : []
    pnlTotals.value = data?.totals ?? null
  } catch (error: any) {
    pnlError.value =
      error?.response?.data?.message ??
      error?.data?.message ??
      'Не удалось загрузить P&L отчёт.'
    pnlPoints.value = []
    pnlTotals.value = null
  } finally {
    loadingPnl.value = false
  }
}

const loadDebts = async () => {
  loadingDebts.value = true
  debtsError.value = ''
  try {
    const params: Record<string, any> = {}
    if (filters.company_id)
      params.company_id = filters.company_id

    const response: any = await $api('reports/debts/summary', { params })
    const data = response?.data ?? response
    debtsSummary.value = Array.isArray(data?.data) ? data.data : []
  } catch (error: any) {
    debtsError.value =
      error?.response?.data?.message ??
      error?.data?.message ??
      'Не удалось загрузить сводку по задолженностям.'
    debtsSummary.value = []
  } finally {
    loadingDebts.value = false
  }
}

const refreshAll = async () => {
  await Promise.all([loadCashflow(), loadPnl(), loadDebts()])
}

const rebuildAndRefresh = async () => {
  rebuilding.value = true
  rebuildError.value = ''
  rebuildResult.value = null
  try {
    const body: Record<string, any> = {}
    if (filters.company_id) body.company_id = filters.company_id
    if (filters.from_month) body.from_month = filters.from_month
    if (filters.to_month) body.to_month = filters.to_month
 
    rebuildResult.value = await $api('reports/rebuild', { method: 'POST', body })
    await refreshAll()
  } catch (error: any) {
    rebuildError.value =
      error?.response?.data?.message ??
      error?.data?.message ??
      'Не удалось пересчитать отчёты.'
  } finally {
    rebuilding.value = false
  }
}

const initFilters = () => {
  if (!filters.company_id && dictionaries.companies.length === 1)
    filters.company_id = Number(dictionaries.companies[0].id)
}

onMounted(async () => {
  await dictionaries.loadCompanies()
  initFilters()
  await refreshAll()
})

// ---- ApexCharts configs ----

const krub = (val: unknown) => Math.round((Number(val ?? 0) || 0) / 1000)

const cashflowSeries = computed(() => {
  const months = cashflowPoints.value
  return [
    {
      name: 'Поступления',
      data: months.map(p => krub(p.inflow_total)),
    },
    {
      name: 'Списания',
      data: months.map(p => krub(p.outflow_total)),
    },
    {
      name: 'Чистый поток',
      data: months.map(p => krub(p.net_cashflow)),
    },
  ]
})

const cashflowCategories = computed(() => cashflowPoints.value.map(p => p.month))

const pnlSeries = computed(() => {
  const rows = pnlPoints.value
  return [
    {
      name: 'Операционная прибыль',
      data: rows.map(p => krub(p.operating_profit)),
    },
    {
      name: 'Финансовый результат',
      data: rows.map(p => krub(p.net_result)),
    },
  ]
})

const pnlCategories = computed(() => pnlPoints.value.map(p => p.month))

const buildBarOptions = (yTitle: string) => {
  const c = theme.current.value.colors
  const v = theme.current.value.variables

  const borderColor = `rgba(${hexToRgb(String(v['border-color']))},${v['border-opacity']})`
  const labelColor = `rgba(${hexToRgb(c['on-surface'])},${v['disabled-opacity']})`

  return {
    chart: {
      parentHeightOffset: 0,
      type: 'bar',
      toolbar: { show: false },
    },
    plotOptions: {
      bar: {
        columnWidth: '40%',
        borderRadiusApplication: 'end',
        borderRadius: 6,
      },
    },
    dataLabels: { enabled: false },
    grid: {
      show: false,
      padding: {
        top: 0,
        bottom: 0,
        left: -10,
        right: -10,
      },
    },
    xaxis: {
      categories: [] as string[],
      axisBorder: { show: true, color: borderColor },
      axisTicks: { show: false },
      labels: {
        style: {
          colors: labelColor,
          fontSize: '13px',
          fontFamily: 'Public Sans',
        },
      },
    },
    yaxis: {
      title: {
        text: yTitle,
        style: {
          color: labelColor,
          fontSize: '12px',
          fontFamily: 'Public Sans',
        },
      },
      labels: {
        style: {
          fontSize: '13px',
          colors: labelColor,
          fontFamily: 'Public Sans',
        },
        formatter: (val: number) => `${formatSum(Math.round(Number(val) || 0))}т`,
      },
    },
    legend: {
      show: true,
      position: 'top',
      horizontalAlign: 'left',
    },
    tooltip: {
      shared: true,
      intersect: false,
      y: {
        formatter: (val: number) => `${formatSum((Number(val) || 0) * 1000)} RUB`,
      },
    },
  }
}

const cashflowOptions = computed(() => {
  const base: any = buildBarOptions('т₽')
  return {
    ...base,
    xaxis: {
      ...base.xaxis,
      categories: cashflowCategories.value,
    },
  }
})

const pnlOptions = computed(() => {
  const base: any = buildBarOptions('т₽')
  return {
    ...base,
    xaxis: {
      ...base.xaxis,
      categories: pnlCategories.value,
    },
  }
})
</script>

<template>
  <div class="d-flex flex-column gap-4">
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <div>
          <div class="text-h6">
            CEO отчёты
          </div>
          <div class="text-caption text-medium-emphasis">
            Материализованные таблицы: денежный поток, P&amp;L, задолженности
          </div>
        </div>

        <VBtn
          color="primary"
          variant="tonal"
          :loading="rebuilding"
          :disabled="rebuilding"
          @click="rebuildAndRefresh"
        >
          Обновить
        </VBtn>
      </VCardTitle>

      <VCardText>
        <VAlert
          v-if="rebuildError"
          type="error"
          variant="tonal"
          class="mb-4"
        >
          {{ rebuildError }}
        </VAlert>

        <VAlert
          v-else-if="rebuildResult"
          type="success"
          variant="tonal"
          class="mb-4"
        >
          <div class="text-body-2">
            Пересчёт выполнен.
          </div>
          <div class="text-caption text-medium-emphasis mt-1">
            Paid: {{ rebuildResult?.source_stats?.paid_total ?? 0 }},
            с ДДС: {{ rebuildResult?.source_stats?.paid_with_cashflow_item ?? 0 }},
            без ДДС: {{ rebuildResult?.source_stats?.paid_without_cashflow_item ?? 0 }}.
          </div>
        </VAlert>

        <VRow dense>
          <VCol
            cols="12"
            md="4"
          >
            <AppSelect
              v-model="filters.company_id"
              :items="dictionaries.companies"
              item-title="name"
              item-value="id"
              label="Компания"
              clearable
            />
          </VCol>
          <VCol
            cols="12"
            md="4"
          >
            <div class="text-caption text-medium-emphasis mt-6">
              По умолчанию показываются последние 12 месяцев. Фильтр по периодам
              можно будет добавить позже.
            </div>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VRow>
      <VCol
        cols="12"
        md="7"
      >
        <VCard class="h-100">
          <VCardTitle>Денежный поток по месяцам</VCardTitle>
          <VCardSubtitle>
            {{ currentCompanyName || 'Выберите компанию' }} ·
            {{ monthRangeLabel }}
          </VCardSubtitle>

          <VProgressLinear
            v-if="loadingCashflow"
            indeterminate
            height="2"
          />

          <VCardText>
            <VAlert
              v-if="cashflowError"
              type="error"
              variant="tonal"
              class="mb-4"
            >
              {{ cashflowError }}
            </VAlert>

            <div
              v-if="!loadingCashflow && !cashflowError && !cashflowPoints.length"
              class="text-medium-emphasis text-center py-8"
            >
              Нет данных для отображения.
            </div>

            <VueApexCharts
              v-else-if="cashflowPoints.length"
              type="bar"
              height="320"
              :options="cashflowOptions"
              :series="cashflowSeries"
            />
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        md="5"
      >
        <VCard class="h-100">
          <VCardTitle>P&amp;L по месяцам</VCardTitle>
          <VCardSubtitle>Кассовый метод, агрегированно</VCardSubtitle>

          <VProgressLinear
            v-if="loadingPnl"
            indeterminate
            height="2"
          />

          <VCardText>
            <VAlert
              v-if="pnlError"
              type="error"
              variant="tonal"
              class="mb-4"
            >
              {{ pnlError }}
            </VAlert>

            <div
              v-if="!loadingPnl && !pnlError && !pnlPoints.length"
              class="text-medium-emphasis text-center py-8"
            >
              Нет данных для отображения.
            </div>

            <VueApexCharts
              v-else-if="pnlPoints.length"
              type="bar"
              height="260"
              :options="pnlOptions"
              :series="pnlSeries"
            />

            <div
              v-if="pnlTotals"
              class="mt-4 text-body-2"
            >
              <div>Выручка (операционная): {{ formatSum(pnlTotals.revenue_operating) }} RUB</div>
              <div>Расходы (операционные): {{ formatSum(pnlTotals.expense_operating) }} RUB</div>
              <div>Операционная прибыль: {{ formatSum(pnlTotals.operating_profit) }} RUB</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VCard>
      <VCardTitle>Задолженности (AR/AP) — текущий срез</VCardTitle>

      <VProgressLinear
        v-if="loadingDebts"
        indeterminate
        height="2"
      />

      <VCardText>
        <VAlert
          v-if="debtsError"
          type="error"
          variant="tonal"
          class="mb-4"
        >
          {{ debtsError }}
        </VAlert>

        <div
          v-if="!loadingDebts && !debtsError && !debtsSummary.length"
          class="text-medium-emphasis text-center py-6"
        >
          Нет данных по задолженностям.
        </div>

        <VTable
          v-else-if="debtsSummary.length"
          class="text-no-wrap"
        >
          <thead>
            <tr>
              <th>Тип</th>
              <th class="text-right">
                Кол-во
              </th>
              <th class="text-right">
                Сумма долга
              </th>
              <th class="text-right">
                Сумма документов
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in debtsSummary"
              :key="row.type"
            >
              <td>{{ row.type_name }}</td>
              <td class="text-right">
                {{ row.records }}
              </td>
              <td class="text-right">
                {{ formatSum(row.total_debt) }}
              </td>
              <td class="text-right">
                {{ formatSum(row.total_amount) }}
              </td>
            </tr>
          </tbody>
        </VTable>
      </VCardText>
    </VCard>
  </div>
</template>
