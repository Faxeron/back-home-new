<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useTheme } from 'vuetify'
import { hexToRgb } from '@layouts/utils'
import { formatSum } from '@/utils/formatters/finance'

const MONTHS_RU_SHORT = ['янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'] as const

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
const currentTab = ref<number>(0)
const refVueApexChart = ref()
const dataRev = ref(0)

const year = computed(() => props.data?.year ?? new Date().getFullYear())
const prevYear = computed(() => props.data?.prev_year ?? (year.value - 1))

const isDark = computed(() => Boolean(theme.global.current.value.dark))
const tooltipTheme = computed(() => isDark.value ? 'dark' : 'light')

const currentYearColor = computed(() => `rgba(${hexToRgb(theme.current.value.colors.warning)}, 1)`)
const prevYearColor = computed(() => {
  const c = theme.current.value.colors
  const v = theme.current.value.variables

  return `rgba(${hexToRgb(c.primary)},${v['dragged-opacity']})`
})

// vue3-apexcharts sometimes doesn't redraw correctly on async data updates.
// Bump key to force a remount when new payload arrives.
watch(
  () => props.data,
  () => {
    dataRev.value++
  },
)

const labels = computed(() => Array.from(MONTHS_RU_SHORT))

const krub = (sumRub: unknown) => Math.round((Number(sumRub ?? 0) || 0) / 1000)

const pad12 = (input: unknown): number[] => {
  const src = Array.isArray(input) ? input : []
  const out = new Array<number>(12)
  for (let i = 0; i < 12; i++) out[i] = Number(src[i] ?? 0) || 0
  return out
}

const chartConfigs = computed(() => {
  const d = props.data
  const c = theme.current.value.colors
  const v = theme.current.value.variables

  const labelPrimaryColor = prevYearColor.value
  const legendColor = `rgba(${hexToRgb(c['on-background'])},${v['high-emphasis-opacity']})`
  const borderColor = `rgba(${hexToRgb(String(v['border-color']))},${v['border-opacity']})`
  const labelColor = `rgba(${hexToRgb(c['on-surface'])},${v['disabled-opacity']})`

  const y = year.value
  const py = prevYear.value

  const baseOptions: any = {
    chart: {
      parentHeightOffset: 0,
      type: 'bar',
      toolbar: { show: false },
    },
    plotOptions: {
      bar: {
        columnWidth: '32%',
        borderRadiusApplication: 'end',
        borderRadius: 6,
        dataLabels: { position: 'top' },
      },
    },
    grid: {
      show: false,
      padding: {
        top: 0,
        bottom: 0,
        left: -10,
        right: -10,
      },
    },
    legend: { show: false },
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
      title: {
        text: '',
        style: {
          color: labelColor,
          fontSize: '12px',
          fontFamily: 'Public Sans',
        },
      },
      labels: {
        offsetX: -15,
        style: {
          fontSize: '13px',
          colors: labelColor,
          fontFamily: 'Public Sans',
        },
      },
    },
    // Apex error guard: shared tooltip requires intersect=false
    tooltip: {
      shared: true,
      intersect: false,
      theme: tooltipTheme.value,
      style: {
        fontSize: '13px',
        fontFamily: 'Public Sans',
      },
    },
    states: {
      hover: { filter: { type: 'none' } },
      active: { filter: { type: 'none' } },
    },
    responsive: [
      {
        breakpoint: 1441,
        options: {
          plotOptions: {
            bar: { columnWidth: '41%' },
          },
        },
      },
      {
        breakpoint: 590,
        options: {
          plotOptions: { bar: { columnWidth: '55%' } },
          yaxis: { labels: { show: false } },
          dataLabels: {
            style: { fontSize: '12px', fontWeight: '400' },
          },
        },
      },
    ],
  }

  const contractsCurrentSumsK = pad12((d?.contracts?.current?.sums ?? []).map(krub))
  const contractsPrevSumsK = pad12((d?.contracts?.prev?.sums ?? []).map(krub))
  const contractsSeries = [
    { name: `${y}`, data: contractsCurrentSumsK },
    { name: `${py}`, data: contractsPrevSumsK },
  ]

  // Base chart: sums in kRUB above bars (both years).
  const contractsOptions: any = {
    ...baseOptions,
    colors: [
      currentYearColor.value,
      labelPrimaryColor,
    ],
    dataLabels: {
      enabled: true,
      formatter: (val: number) => `${formatSum(Math.round(Number(val) || 0))}т`,
      offsetY: -25,
      style: {
        fontSize: '15px',
        fontFamily: 'Public Sans',
        fontWeight: 600,
        colors: [legendColor],
      },
    },
    yaxis: {
      ...baseOptions.yaxis,
      title: { ...baseOptions.yaxis.title, text: 'т₽' },
      labels: {
        ...baseOptions.yaxis.labels,
        formatter: (val: number) => `${formatSum(Math.round(Number(val) || 0))}т`,
      },
    },
    tooltip: {
      ...baseOptions.tooltip,
      y: {
        formatter: (_val: number, opts: any) => {
          const i = Number(opts?.dataPointIndex ?? 0) || 0
          const s = Number(opts?.seriesIndex ?? 0) || 0
          const sumRub = s === 0 ? (d?.contracts?.current?.sums?.[i] ?? 0) : (d?.contracts?.prev?.sums?.[i] ?? 0)
          const count = s === 0 ? (d?.contracts?.current?.counts?.[i] ?? 0) : (d?.contracts?.prev?.counts?.[i] ?? 0)
          return `${formatSum(sumRub)} RUB (кол-во: ${count})`
        },
      },
    },
  }

  const estimatesCurrent = pad12((d?.estimates?.current?.counts ?? []).map(v => Number(v ?? 0) || 0))
  const estimatesPrev = pad12((d?.estimates?.prev?.counts ?? []).map(v => Number(v ?? 0) || 0))

  const estimatesOptions: any = {
    ...baseOptions,
    colors: [
      currentYearColor.value,
      labelPrimaryColor,
    ],
    dataLabels: {
      enabled: true,
      formatter: (val: number) => `${Math.round(Number(val) || 0)}`,
      offsetY: -25,
      style: {
        fontSize: '15px',
        colors: [legendColor],
        fontWeight: 600,
        fontFamily: 'Public Sans',
      },
    },
    tooltip: {
      ...baseOptions.tooltip,
      y: { formatter: (val: number) => `${Math.round(Number(val) || 0)} шт.` },
    },
    yaxis: {
      ...baseOptions.yaxis,
      labels: {
        ...baseOptions.yaxis.labels,
        formatter: (val: number) => `${Math.round(Number(val) || 0)}`,
      },
    },
  }

  const profitCurrent = pad12((d?.profit?.current?.net_sums ?? []).map(krub))
  const profitPrev = pad12((d?.profit?.prev?.net_sums ?? []).map(krub))

  const profitOptions: any = {
    ...baseOptions,
    colors: [
      currentYearColor.value,
      labelPrimaryColor,
    ],
    dataLabels: {
      enabled: true,
      formatter: (val: number) => `${formatSum(Math.round(Number(val) || 0))}т`,
      offsetY: -25,
      style: {
        fontSize: '15px',
        colors: [legendColor],
        fontWeight: 600,
        fontFamily: 'Public Sans',
      },
    },
    yaxis: {
      ...baseOptions.yaxis,
      title: { ...baseOptions.yaxis.title, text: 'т₽' },
      labels: {
        ...baseOptions.yaxis.labels,
        formatter: (val: number) => `${formatSum(Math.round(Number(val) || 0))}т`,
      },
    },
    tooltip: {
      ...baseOptions.tooltip,
      y: {
        formatter: (val: number) => `${formatSum((Number(val) || 0) * 1000)} RUB`,
      },
    },
  }

  return [
    {
      title: 'Договоры',
      icon: 'tabler-file-text',
      series: contractsSeries,
      chartOptions: contractsOptions,
    },
    {
      title: 'Сметы',
      icon: 'tabler-calculator',
      series: [
        { name: `${y}`, data: estimatesCurrent },
        { name: `${py}`, data: estimatesPrev },
      ],
      chartOptions: estimatesOptions,
    },
    {
      title: 'Прибыль',
      icon: 'tabler-trending-up',
      series: [
        { name: `${y}`, data: profitCurrent },
        { name: `${py}`, data: profitPrev },
      ],
      chartOptions: profitOptions,
    },
  ]
})

</script>

<template>
  <VCard
    title="Годовой разрез"
    class="h-100"
  >
    <template #subtitle>
      <div class="d-flex align-center flex-wrap gap-2">
        <span
          class="year-dot"
          :style="{ backgroundColor: currentYearColor }"
        />
        <span class="font-weight-medium">
          {{ year }}
        </span>
        <span class="text-medium-emphasis">
          vs
        </span>
        <span
          class="year-dot"
          :style="{ backgroundColor: prevYearColor }"
        />
        <span class="font-weight-medium">
          {{ prevYear }}
        </span>
      </div>
    </template>

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

      <VSlideGroup
        v-model="currentTab"
        show-arrows
        mandatory
        class="mb-10"
      >
        <VSlideGroupItem
          v-for="(report, index) in chartConfigs"
          :key="report.title"
          v-slot="{ isSelected, toggle }"
          :value="index"
        >
          <div
            style="block-size: 100px; inline-size: 110px;"
            :style="isSelected ? 'border-color:rgb(var(--v-theme-primary)) !important' : ''"
            :class="isSelected ? 'border' : 'border border-dashed'"
            class="d-flex flex-column justify-center align-center cursor-pointer rounded py-4 px-5 me-4"
            @click="toggle"
          >
            <VAvatar
              rounded
              size="38"
              :color="isSelected ? 'primary' : ''"
              variant="tonal"
              class="mb-2"
            >
              <VIcon
                size="22"
                :icon="report.icon"
              />
            </VAvatar>
            <h6 class="text-base font-weight-medium mb-0">
              {{ report.title }}
            </h6>
          </div>
        </VSlideGroupItem>
      </VSlideGroup>

      <VueApexCharts
        v-if="chartConfigs.length && Number(currentTab) === 0"
        ref="refVueApexChart"
        :key="`contracts-${dataRev}`"
        type="bar"
        :options="chartConfigs[0]?.chartOptions"
        :series="chartConfigs[0]?.series"
        height="230"
        class="mt-3"
      />
      <VueApexCharts
        v-else-if="chartConfigs.length"
        ref="refVueApexChart"
        :key="`${currentTab}-${dataRev}`"
        type="bar"
        :options="chartConfigs[Number(currentTab)]?.chartOptions"
        :series="chartConfigs[Number(currentTab)]?.series"
        height="230"
        class="mt-3"
      />
      <div
        v-else
        class="text-medium-emphasis text-center py-12"
      >
        Нет данных.
      </div>
    </VCardText>
  </VCard>
</template>

<style scoped>
.year-dot {
  border-radius: 999px;
  block-size: 10px;
  inline-size: 10px;
}

/* Make tooltip text readable (some themes apply too-low opacity). */
:deep(.apexcharts-tooltip) {
  opacity: 1 !important;
}

:deep(.apexcharts-tooltip *) {
  opacity: 1 !important;
}

:deep(.apexcharts-tooltip.apexcharts-theme-light) {
  color: rgba(15, 20, 34, 0.92) !important;
}

:deep(.apexcharts-tooltip.apexcharts-theme-dark) {
  color: rgba(255, 255, 255, 0.92) !important;
}
</style>
