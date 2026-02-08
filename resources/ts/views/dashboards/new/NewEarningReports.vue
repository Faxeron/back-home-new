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
const currentTab = ref<number>(0)
const refVueApexChart = ref()

const labels = computed(() => props.data?.labels ?? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'])

const krub = (sumRub: unknown) => Math.round((Number(sumRub ?? 0) || 0) / 1000)

const chartConfigs = computed(() => {
  const d = props.data
  const c = theme.current.value.colors
  const v = theme.current.value.variables

  const labelPrimaryColor = `rgba(${hexToRgb(c.primary)},${v['dragged-opacity']})`
  const legendColor = `rgba(${hexToRgb(c['on-background'])},${v['high-emphasis-opacity']})`
  const borderColor = `rgba(${hexToRgb(String(v['border-color']))},${v['border-opacity']})`
  const labelColor = `rgba(${hexToRgb(c['on-surface'])},${v['disabled-opacity']})`

  const year = d?.year ?? new Date().getFullYear()
  const prevYear = d?.prev_year ?? (year - 1)

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
    tooltip: { shared: true },
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

  const contractsCurrentSumsK = (d?.contracts?.current?.sums ?? []).map(krub)
  const contractsPrevSumsK = (d?.contracts?.prev?.sums ?? []).map(krub)
  const contractsCurrentCounts = d?.contracts?.current?.counts ?? []
  const contractsPrevCounts = d?.contracts?.prev?.counts ?? []

  const contractsAnnotations: any[] = []
  for (let seriesIndex = 0; seriesIndex < 2; seriesIndex++) {
    for (let i = 0; i < 12; i++) {
      const y = Number((seriesIndex === 0 ? contractsCurrentSumsK[i] : contractsPrevSumsK[i]) ?? 0) || 0
      const x = labels.value[i] ?? String(i + 1)
      contractsAnnotations.push({
        x,
        y,
        seriesIndex,
        label: {
          borderColor: 'transparent',
          style: {
            fontSize: '15px',
            fontFamily: 'Public Sans',
            color: legendColor,
            background: 'transparent',
            fontWeight: 600,
          },
          offsetY: -25,
          text: `${formatSum(Math.round(y))}k`,
        },
        marker: { size: 0 },
      })
    }
  }

  const contractsOptions: any = {
    ...baseOptions,
    colors: [
      `rgba(${hexToRgb(c.primary)}, 1)`,
      labelPrimaryColor,
    ],
    plotOptions: {
      ...baseOptions.plotOptions,
      bar: {
        ...baseOptions.plotOptions.bar,
        dataLabels: { position: 'center' },
      },
    },
    dataLabels: {
      enabled: true,
      formatter: (_val: number, opts: any) => {
        const i = Number(opts?.dataPointIndex ?? 0) || 0
        const s = Number(opts?.seriesIndex ?? 0) || 0
        const count = s === 0 ? contractsCurrentCounts[i] : contractsPrevCounts[i]
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
      ...baseOptions.yaxis,
      title: { ...baseOptions.yaxis.title, text: 'kRUB' },
      labels: {
        ...baseOptions.yaxis.labels,
        formatter: (val: number) => `${formatSum(Math.round(Number(val) || 0))}k`,
      },
    },
    tooltip: {
      shared: true,
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
    annotations: { points: contractsAnnotations },
  }

  const estimatesCurrent = (d?.estimates?.current?.counts ?? []).map(v => Number(v ?? 0) || 0)
  const estimatesPrev = (d?.estimates?.prev?.counts ?? []).map(v => Number(v ?? 0) || 0)

  const estimatesOptions: any = {
    ...baseOptions,
    colors: [
      `rgba(${hexToRgb(c.primary)}, 1)`,
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
      shared: true,
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

  const profitCurrent = (d?.profit?.current?.net_sums ?? []).map(krub)
  const profitPrev = (d?.profit?.prev?.net_sums ?? []).map(krub)

  const profitOptions: any = {
    ...baseOptions,
    colors: [
      `rgba(${hexToRgb(c.success)}, 1)`,
      `rgba(${hexToRgb(c.info)},${v['dragged-opacity']})`,
    ],
    dataLabels: {
      enabled: true,
      formatter: (val: number) => `${formatSum(Math.round(Number(val) || 0))}k`,
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
      title: { ...baseOptions.yaxis.title, text: 'kRUB' },
      labels: {
        ...baseOptions.yaxis.labels,
        formatter: (val: number) => `${formatSum(Math.round(Number(val) || 0))}k`,
      },
    },
    tooltip: {
      shared: true,
      y: {
        formatter: (val: number) => `${formatSum((Number(val) || 0) * 1000)} RUB`,
      },
    },
  }

  return [
    {
      title: 'Договоры',
      icon: 'tabler-file-text',
      series: [
        { name: `${year}`, data: contractsCurrentSumsK.length ? contractsCurrentSumsK : Array(12).fill(0) },
        { name: `${prevYear}`, data: contractsPrevSumsK.length ? contractsPrevSumsK : Array(12).fill(0) },
      ],
      chartOptions: contractsOptions,
    },
    {
      title: 'Сметы',
      icon: 'tabler-calculator',
      series: [
        { name: `${year}`, data: estimatesCurrent.length ? estimatesCurrent : Array(12).fill(0) },
        { name: `${prevYear}`, data: estimatesPrev.length ? estimatesPrev : Array(12).fill(0) },
      ],
      chartOptions: estimatesOptions,
    },
    {
      title: 'Прибыль',
      icon: 'tabler-trending-up',
      series: [
        { name: `${year}`, data: profitCurrent.length ? profitCurrent : Array(12).fill(0) },
        { name: `${prevYear}`, data: profitPrev.length ? profitPrev : Array(12).fill(0) },
      ],
      chartOptions: profitOptions,
    },
  ]
})

const subtitle = computed(() => {
  const d = props.data
  if (!d) return 'Yearly Overview'
  return `${d.year} vs ${d.prev_year}`
})
</script>

<template>
  <VCard
    title="Earning Reports"
    :subtitle="subtitle"
    class="h-100"
  >
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
        v-if="chartConfigs.length"
        ref="refVueApexChart"
        :key="currentTab"
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
