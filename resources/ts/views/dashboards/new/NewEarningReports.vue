<script setup lang="ts">
import { computed, ref } from 'vue'
import { useTheme } from 'vuetify'
import { hexToRgb } from '@layouts/utils'
import { formatSum } from '@/utils/formatters/finance'

type YearSeries = {
  counts?: number[]
  sums?: number[]
  net_sums?: number[]
  total_count?: number
  total_sum?: number
  total_net_sum?: number
}

type Payload = {
  currency: 'RUB' | string
  year: number
  prev_year: number
  labels: string[]
  contracts: { current: YearSeries; prev: YearSeries }
  estimates: { current: YearSeries; prev: YearSeries }
  profit: { current: YearSeries; prev: YearSeries }
}

const props = defineProps<{
  data: Payload | null
  loading?: boolean
  error?: string
}>()

const emit = defineEmits<{
  (e: 'refresh'): void
}>()

const theme = useTheme()
const currentTab = ref<'contracts' | 'estimates' | 'profit'>('contracts')

const labels = computed(() => props.data?.labels ?? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'])

const krub = (sumRub: unknown) => Math.round((Number(sumRub ?? 0) || 0) / 1000)

const chartBase = computed(() => {
  const c = theme.current.value.colors
  const v = theme.current.value.variables
  const labelColor = `rgba(${hexToRgb(c['on-surface'])},${v['disabled-opacity']})`
  const borderColor = `rgba(${hexToRgb(String(v['border-color']))},${v['border-opacity']})`

  return {
    chart: {
      parentHeightOffset: 0,
      toolbar: { show: false },
      type: 'bar',
    },
    grid: {
      borderColor,
      strokeDashArray: 4,
      padding: {
        top: 6,
        left: 0,
        right: 8,
        bottom: 0,
      },
    },
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: '42%',
        borderRadius: 8,
        borderRadiusApplication: 'end',
        borderRadiusWhenStacked: 'all',
      },
    },
    legend: {
      position: 'top',
      horizontalAlign: 'left',
      fontFamily: 'Public Sans',
      fontSize: '13px',
      labels: { colors: labelColor },
      itemMargin: { horizontal: 12, vertical: 4 },
      markers: { width: 10, height: 10, radius: 10, offsetX: -2, offsetY: 1 },
    },
    dataLabels: { enabled: false },
    xaxis: {
      categories: labels.value,
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
      tickAmount: 6,
      labels: {
        style: { colors: labelColor, fontSize: '13px', fontFamily: 'Public Sans' },
      },
    },
    tooltip: {
      shared: true,
    },
    states: {
      hover: { filter: { type: 'none' } },
      active: { filter: { type: 'none' } },
    },
  } as any
})

const colors = computed(() => {
  const c = theme.current.value.colors
  const v = theme.current.value.variables

  return {
    current: `rgba(${hexToRgb(c.primary)}, 1)`,
    prev: `rgba(${hexToRgb(c.info)},${v['dragged-opacity']})`,
    prevStrong: `rgba(${hexToRgb(c.info)}, 1)`,
    label: `rgba(${hexToRgb(c['on-background'])},${v['high-emphasis-opacity']})`,
  }
})

const contractsSeries = computed(() => {
  const d = props.data
  if (!d) return []

  const currentSums = (d.contracts.current.sums ?? []).map(krub)
  const prevSums = (d.contracts.prev.sums ?? []).map(krub)

  return [
    { name: `${d.year}`, data: currentSums },
    { name: `${d.prev_year}`, data: prevSums },
  ]
})

const estimatesSeries = computed(() => {
  const d = props.data
  if (!d) return []

  return [
    { name: `${d.year}`, data: (d.estimates.current.counts ?? []).map(v => Number(v ?? 0) || 0) },
    { name: `${d.prev_year}`, data: (d.estimates.prev.counts ?? []).map(v => Number(v ?? 0) || 0) },
  ]
})

const profitSeries = computed(() => {
  const d = props.data
  if (!d) return []

  const current = (d.profit.current.net_sums ?? []).map(krub)
  const prev = (d.profit.prev.net_sums ?? []).map(krub)

  return [
    { name: `${d.year}`, data: current },
    { name: `${d.prev_year}`, data: prev },
  ]
})

const contractsAnnotations = computed(() => {
  const d = props.data
  if (!d) return []

  const series = [
    (d.contracts.current.sums ?? []).map(krub),
    (d.contracts.prev.sums ?? []).map(krub),
  ]

  const points: any[] = []
  for (let seriesIndex = 0; seriesIndex < series.length; seriesIndex++) {
    for (let i = 0; i < 12; i++) {
      const y = Number(series[seriesIndex][i] ?? 0) || 0
      const x = labels.value[i] ?? String(i + 1)
      const text = `${formatSum(y)} kRUB`

      points.push({
        x,
        y,
        seriesIndex,
        label: {
          borderColor: 'transparent',
          style: {
            fontSize: '11px',
            fontFamily: 'Public Sans',
            color: colors.value.label,
            background: 'transparent',
          },
          offsetY: -14,
          text,
        },
        marker: { size: 0 },
      })
    }
  }

  return points
})

const contractsOptions = computed(() => {
  const d = props.data
  if (!d) return chartBase.value

  const currentCounts = d.contracts.current.counts ?? []
  const prevCounts = d.contracts.prev.counts ?? []

  return {
    ...chartBase.value,
    colors: [colors.value.current, colors.value.prevStrong],
    plotOptions: {
      ...chartBase.value.plotOptions,
      bar: {
        ...chartBase.value.plotOptions.bar,
        dataLabels: { position: 'center' },
      },
    },
    dataLabels: {
      enabled: true,
      formatter: (_val: number, opts: any) => {
        const i = Number(opts?.dataPointIndex ?? 0) || 0
        const s = Number(opts?.seriesIndex ?? 0) || 0
        const count = s === 0 ? currentCounts[i] : prevCounts[i]
        return `${Number(count ?? 0) || 0}`
      },
      style: {
        fontSize: '12px',
        fontFamily: 'Public Sans',
        fontWeight: 700,
        colors: ['#fff'],
      },
      dropShadow: { enabled: true, opacity: 0.25, blur: 2, left: 0, top: 1 },
    },
    yaxis: {
      ...chartBase.value.yaxis,
      labels: {
        ...chartBase.value.yaxis.labels,
        formatter: (val: number) => `${formatSum(Math.round(Number(val) || 0))} kRUB`,
      },
    },
    tooltip: {
      shared: true,
      y: {
        formatter: (val: number, opts: any) => {
          const i = Number(opts?.dataPointIndex ?? 0) || 0
          const s = Number(opts?.seriesIndex ?? 0) || 0
          const sumRub = s === 0 ? (d.contracts.current.sums?.[i] ?? 0) : (d.contracts.prev.sums?.[i] ?? 0)
          const count = s === 0 ? (d.contracts.current.counts?.[i] ?? 0) : (d.contracts.prev.counts?.[i] ?? 0)
          return `${formatSum(sumRub)} RUB (кол-во: ${count})`
        },
      },
    },
    annotations: {
      points: contractsAnnotations.value,
    },
  } as any
})

const estimatesOptions = computed(() => {
  const d = props.data
  if (!d) return chartBase.value

  return {
    ...chartBase.value,
    colors: [colors.value.current, colors.value.prevStrong],
    plotOptions: {
      ...chartBase.value.plotOptions,
      bar: {
        ...chartBase.value.plotOptions.bar,
        dataLabels: { position: 'center' },
      },
    },
    dataLabels: {
      enabled: true,
      formatter: (val: number) => `${Math.round(Number(val) || 0)}`,
      style: {
        fontSize: '12px',
        fontFamily: 'Public Sans',
        fontWeight: 700,
        colors: ['#fff'],
      },
      dropShadow: { enabled: true, opacity: 0.25, blur: 2, left: 0, top: 1 },
    },
    tooltip: {
      shared: true,
      y: { formatter: (val: number) => `${Math.round(Number(val) || 0)} шт.` },
    },
    yaxis: {
      ...chartBase.value.yaxis,
      labels: {
        ...chartBase.value.yaxis.labels,
        formatter: (val: number) => `${Math.round(Number(val) || 0)}`,
      },
    },
  } as any
})

const profitOptions = computed(() => {
  const d = props.data
  if (!d) return chartBase.value

  const all = [...(profitSeries.value?.[0]?.data ?? []), ...(profitSeries.value?.[1]?.data ?? [])].map(v => Number(v ?? 0) || 0)
  const min = all.length ? Math.min(...all) : 0
  const max = all.length ? Math.max(...all) : 0
  const pad = Math.max(10, Math.round((max - min) * 0.08))

  return {
    ...chartBase.value,
    colors: [colors.value.current, colors.value.prevStrong],
    dataLabels: {
      enabled: true,
      offsetY: -10,
      formatter: (val: number) => `${formatSum(Math.round(Number(val) || 0))} kRUB`,
      style: {
        fontSize: '11px',
        fontFamily: 'Public Sans',
        fontWeight: 700,
        colors: [colors.value.label],
      },
    },
    yaxis: {
      ...chartBase.value.yaxis,
      min: min - pad,
      max: max + pad,
      labels: {
        ...chartBase.value.yaxis.labels,
        formatter: (val: number) => `${formatSum(Math.round(Number(val) || 0))} kRUB`,
      },
    },
    tooltip: {
      shared: true,
      y: {
        formatter: (val: number) => `${formatSum((Number(val) || 0) * 1000)} RUB`,
      },
    },
  } as any
})

const headerTitle = computed(() => {
  if (currentTab.value === 'contracts') return 'Договоры по месяцам'
  if (currentTab.value === 'estimates') return 'Сметы по месяцам'
  return 'Прибыль по месяцам'
})

const headerSubtitle = computed(() => {
  const d = props.data
  if (!d) return 'Текущий год vs предыдущий год'
  return `${d.year} vs ${d.prev_year}`
})

const totalsLeft = computed(() => {
  const d = props.data
  if (!d) return null

  if (currentTab.value === 'contracts') {
    return [
      { year: d.year, line1: `${d.contracts.current.total_count ?? 0} шт.`, line2: `${formatSum(krub(d.contracts.current.total_sum))} kRUB` },
      { year: d.prev_year, line1: `${d.contracts.prev.total_count ?? 0} шт.`, line2: `${formatSum(krub(d.contracts.prev.total_sum))} kRUB` },
    ]
  }

  if (currentTab.value === 'estimates') {
    return [
      { year: d.year, line1: `${d.estimates.current.total_count ?? 0} шт.`, line2: '' },
      { year: d.prev_year, line1: `${d.estimates.prev.total_count ?? 0} шт.`, line2: '' },
    ]
  }

  return [
    { year: d.year, line1: `${formatSum(krub(d.profit.current.total_net_sum))} kRUB`, line2: '' },
    { year: d.prev_year, line1: `${formatSum(krub(d.profit.prev.total_net_sum))} kRUB`, line2: '' },
  ]
})
</script>

<template>
  <VCard class="h-100">
    <VCardItem class="pb-sm-0">
      <div>
        <VCardTitle>Earning Reports</VCardTitle>
        <VCardSubtitle>{{ headerSubtitle }}</VCardSubtitle>
      </div>

      <template #append>
        <div class="d-flex align-center gap-2">
          <VBtn
            size="small"
            variant="tonal"
            :disabled="props.loading"
            @click="emit('refresh')"
          >
            Обновить
          </VBtn>
        </div>
      </template>
    </VCardItem>

    <VProgressLinear
      v-if="props.loading"
      indeterminate
      height="2"
    />

    <VCardText>
      <VAlert
        v-if="props.error"
        type="error"
        variant="tonal"
        class="mb-4"
      >
        {{ props.error }}
      </VAlert>

      <div class="d-flex align-center justify-space-between flex-wrap gap-3 mb-4">
        <div>
          <div class="text-sm text-medium-emphasis">
            {{ headerTitle }}
          </div>
          <div class="text-body-1 font-weight-medium">
            Внутри столбца: количество, сверху: сумма (kRUB)
          </div>
        </div>

        <div class="d-flex align-center gap-2 flex-wrap">
          <VBtn
            size="small"
            :variant="currentTab === 'contracts' ? 'flat' : 'tonal'"
            color="primary"
            @click="currentTab = 'contracts'"
          >
            Договоры
          </VBtn>
          <VBtn
            size="small"
            :variant="currentTab === 'estimates' ? 'flat' : 'tonal'"
            color="primary"
            @click="currentTab = 'estimates'"
          >
            Сметы
          </VBtn>
          <VBtn
            size="small"
            :variant="currentTab === 'profit' ? 'flat' : 'tonal'"
            color="primary"
            @click="currentTab = 'profit'"
          >
            Прибыль
          </VBtn>
        </div>
      </div>

      <VRow>
        <VCol
          cols="12"
          md="4"
          class="d-flex flex-column"
        >
          <div class="border rounded pa-4 h-100">
            <div class="text-sm text-medium-emphasis mb-3">
              Итоги за год
            </div>

            <div
              v-if="totalsLeft"
              class="d-flex flex-column gap-3"
            >
              <div
                v-for="row in totalsLeft"
                :key="row.year"
                class="d-flex align-center justify-space-between"
              >
                <div class="min-w-0">
                  <div class="text-base font-weight-semibold">
                    {{ row.year }}
                  </div>
                  <div class="text-sm text-medium-emphasis">
                    {{ row.line1 }}
                  </div>
                </div>

                <div
                  v-if="row.line2"
                  class="text-sm font-weight-semibold"
                >
                  {{ row.line2 }}
                </div>
              </div>
            </div>

            <div
              v-else
              class="text-medium-emphasis"
            >
              Нет данных.
            </div>
          </div>
        </VCol>

        <VCol
          cols="12"
          md="8"
        >
          <div class="border rounded pa-4">
            <VueApexCharts
              v-if="props.data && currentTab === 'contracts'"
              type="bar"
              height="320"
              :options="contractsOptions"
              :series="contractsSeries"
            />
            <VueApexCharts
              v-else-if="props.data && currentTab === 'estimates'"
              type="bar"
              height="320"
              :options="estimatesOptions"
              :series="estimatesSeries"
            />
            <VueApexCharts
              v-else-if="props.data && currentTab === 'profit'"
              type="bar"
              height="320"
              :options="profitOptions"
              :series="profitSeries"
            />

            <div
              v-else
              class="text-medium-emphasis text-center py-12"
            >
              Нет данных.
            </div>
          </div>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>

