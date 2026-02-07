<script setup lang="ts">
import { computed } from 'vue'
import { useTheme } from 'vuetify'
import { getAreaChartSplineConfig } from '@core/libs/apex-chart/apexCharConfig'
import { formatSum } from '@/utils/formatters/finance'

type CashflowPoint = {
  month: string // YYYY-MM
  incomes_sum: number
  expenses_sum: number
  net_sum: number
  currency?: string | null
}

const props = defineProps<{
  points: CashflowPoint[]
  loading?: boolean
  error?: string
}>()

const emit = defineEmits<{
  (e: 'refresh'): void
}>()

const theme = useTheme()

const categories = computed(() => {
  const fmt = new Intl.DateTimeFormat('ru-RU', { month: 'short' })
  return (props.points ?? []).map(p => {
    const [y, m] = String(p.month ?? '').split('-').map(x => Number(x))
    if (!y || !m) return String(p.month ?? '')
    // Remove trailing dot that "short" often includes in ru-RU.
    return fmt.format(new Date(y, m - 1, 1)).replace('.', '')
  })
})

// Scale values in units of 1k RUB so the y-axis isn't shown in millions.
// Labels and tooltip are shown as kRUB.
const scale = 1_000
const unitLabel = 'kRUB'
const series = computed(() => {
  const income = (props.points ?? []).map(p => (Number(p.incomes_sum ?? 0) || 0) / scale)
  const expense = (props.points ?? []).map(p => (Number(p.expenses_sum ?? 0) || 0) / scale)
  const net = (props.points ?? []).map(p => (Number(p.net_sum ?? 0) || 0) / scale)

  return [
    { name: 'Поступления', data: income },
    { name: 'Списания', data: expense },
    { name: 'Разница', data: net },
  ]
})

const yAxis = computed(() => {
  const all = series.value.flatMap(s => s.data as number[])
  const minVal = all.length ? Math.min(...all) : 0
  const maxVal = all.length ? Math.max(...all) : 0

  const min = Math.floor(minVal)
  const max = Math.ceil(maxVal)
  const span = Math.max(1, max - min)

  // Prefer steps that are multiples of 10 kRUB (i.e., 10k RUB) but adapt to data range.
  const niceStep = (raw: number) => {
    if (!Number.isFinite(raw) || raw <= 0) return 10
    const pow = 10 ** Math.floor(Math.log10(raw))
    const n = raw / pow
    const base = n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10
    return base * pow
  }

  const targetTicks = 10
  const rawStep = span / targetTicks
  const step = Math.max(10, niceStep(rawStep / 10) * 10)

  const axisMin = Math.floor(min / step) * step
  const axisMax = Math.ceil(max / step) * step
  const tickAmount = Math.max(2, Math.min(12, (axisMax - axisMin) / step))

  return { axisMin, axisMax, tickAmount }
})

const chartOptions = computed(() => {
  const base: any = getAreaChartSplineConfig(theme.current.value)
  const c = theme.current.value.colors

  return {
    ...base,
    chart: {
      ...(base.chart ?? {}),
      type: 'area',
      height: 320,
      parentHeightOffset: 0,
      toolbar: { show: false },
      zoom: { enabled: false },
      animations: { enabled: true, easing: 'easeinout', speed: 700 },
    },
    colors: [c.success, c.info, c.warning],
    stroke: {
      show: true,
      curve: 'smooth',
      width: 2,
    },
    fill: {
      type: 'gradient',
      gradient: {
        shadeIntensity: 0.15,
        opacityFrom: 0.35,
        opacityTo: 0.05,
        stops: [0, 90, 100],
      },
    },
    dataLabels: { enabled: false },
    markers: {
      size: 0,
      hover: { size: 5 },
    },
    legend: {
      ...(base.legend ?? {}),
      show: true,
      position: 'top',
      horizontalAlign: 'left',
    },
    tooltip: {
      shared: true,
      y: {
        formatter: (val: number) => `${formatSum(Math.round(Number(val) || 0))} ${unitLabel}`,
      },
    },
    xaxis: {
      ...(base.xaxis ?? {}),
      categories: categories.value,
    },
    yaxis: {
      ...(base.yaxis ?? {}),
      min: yAxis.value.axisMin,
      max: yAxis.value.axisMax,
      tickAmount: yAxis.value.tickAmount,
      labels: {
        ...(base.yaxis?.labels ?? {}),
        formatter: (val: number) => `${formatSum(Math.round(Number(val) || 0))} ${unitLabel}`,
      },
    },
  }
})
</script>

<template>
  <VCard class="h-100">
    <VCardItem>
      <div>
        <VCardTitle>Движение денег за год</VCardTitle>
        <VCardSubtitle>Поступления, списания и разница по месяцам</VCardSubtitle>
      </div>

      <template #append>
        <div class="d-flex align-center gap-2">
          <VBtn
            size="small"
            variant="tonal"
            :to="{ path: '/finance/transactions' }"
          >
            Открыть
          </VBtn>
          <IconBtn @click="emit('refresh')">
            <VIcon icon="tabler-refresh" />
          </IconBtn>
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

      <div
        v-if="!props.loading && !props.error && (!props.points || props.points.length === 0)"
        class="text-medium-emphasis text-center py-10"
      >
        Нет данных за период.
      </div>

      <VueApexCharts
        v-else-if="props.points && props.points.length > 0"
        type="area"
        height="320"
        :options="chartOptions"
        :series="series"
      />
    </VCardText>
  </VCard>
</template>
