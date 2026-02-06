<script setup lang="ts">
import { useTheme } from 'vuetify'
import { hexToRgb } from '@layouts/utils'
import { formatSum } from '@/utils/formatters/finance'

const props = defineProps<{
  labels: string[]
  weekSums: number[]
  monthAccruedSum: number
  monthContractsCount: number
  currency?: string
}>()

const vuetifyTheme = useTheme()
const currency = computed(() => props.currency ?? 'RUB')

const series = computed(() => [{ data: props.weekSums ?? [] }])

const chartOptions = computed(() => {
  const currentTheme = vuetifyTheme.current.value.colors
  const variableTheme = vuetifyTheme.current.value.variables

  return {
    chart: {
      height: 90,
      type: 'line',
      parentHeightOffset: 0,
      toolbar: { show: false },
    },
    grid: {
      borderColor: `rgba(${hexToRgb(String(variableTheme['border-color']))},${variableTheme['border-opacity']})`,
      strokeDashArray: 6,
      xaxis: { lines: { show: true } },
      yaxis: { lines: { show: false } },
      padding: { top: -18, left: -4, right: 7, bottom: -10 },
    },
    colors: [currentTheme.success],
    stroke: { width: 2 },
    tooltip: { enabled: false },
    xaxis: {
      categories: props.labels ?? [],
      labels: { show: false },
      axisTicks: { show: false },
      axisBorder: { show: false },
    },
    yaxis: { labels: { show: false } },
    markers: {
      size: 3.5,
      fillColor: currentTheme.success,
      strokeColors: 'transparent',
      strokeWidth: 3.2,
      discrete: [
        {
          seriesIndex: 0,
          dataPointIndex: Math.max(0, (props.weekSums?.length ?? 1) - 1),
          fillColor: currentTheme.surface,
          strokeColor: currentTheme.success,
          size: 5,
          shape: 'circle',
        },
      ],
      hover: { size: 5.5 },
    },
  }
})
</script>

<template>
  <VCard>
    <VCardItem class="pb-3">
      <VCardTitle>Зарплата</VCardTitle>
      <VCardSubtitle>Текущий месяц</VCardSubtitle>
    </VCardItem>

    <VCardText>
      <VueApexCharts
        type="line"
        :options="chartOptions"
        :series="series"
        :height="68"
      />

      <div class="d-flex align-center justify-space-between gap-x-2 mt-3">
        <h4 class="text-h4 text-center font-weight-medium">
          {{ formatSum(props.monthAccruedSum ?? 0) }} {{ currency }}
        </h4>
        <span class="text-sm text-medium-emphasis">
          Договоров: {{ props.monthContractsCount ?? 0 }}
        </span>
      </div>
    </VCardText>
  </VCard>
</template>

