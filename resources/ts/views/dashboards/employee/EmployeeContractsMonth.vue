<script setup lang="ts">
import { useTheme } from 'vuetify'
import { hexToRgb } from '@layouts/utils'
import { formatSum } from '@/utils/formatters/finance'

const props = defineProps<{
  title?: string
  subtitle?: string
  labels: string[]
  currentWeekCounts: number[]
  prevWeekCounts: number[]
  currentMonthCount: number
  currentMonthSum: number
  prevMonthCount: number
  prevMonthSum: number
  currentMonthLabel: string
  prevMonthLabel: string
  currency?: string
}>()

const vuetifyTheme = useTheme()
const currency = computed(() => props.currency ?? 'RUB')

const series = computed(() => ([
  { name: props.currentMonthLabel, data: props.currentWeekCounts ?? [] },
  { name: props.prevMonthLabel, data: props.prevWeekCounts ?? [] },
]))

const chartOptions = computed(() => {
  const currentTheme = vuetifyTheme.current.value.colors
  const variableTheme = vuetifyTheme.current.value.variables

  const labelColor = `rgba(${hexToRgb(currentTheme['on-surface'])},${variableTheme['disabled-opacity']})`

  return {
    chart: {
      type: 'bar',
      parentHeightOffset: 0,
      toolbar: { show: false },
    },
    plotOptions: {
      bar: {
        columnWidth: '52%',
        startingShape: 'rounded',
        endingShape: 'rounded',
        borderRadius: 6,
      },
    },
    colors: [currentTheme.primary, currentTheme.info],
    dataLabels: { enabled: false },
    legend: { show: false },
    tooltip: { enabled: false },
    grid: {
      show: false,
      padding: { top: -10, bottom: -8, left: -10, right: 10 },
    },
    xaxis: {
      categories: props.labels ?? [],
      axisBorder: { show: false },
      axisTicks: { show: false },
      labels: {
        style: {
          colors: labelColor,
          fontSize: '12px',
          fontFamily: 'Public sans',
        },
      },
    },
    yaxis: { labels: { show: false } },
    states: {
      hover: { filter: { type: 'none' } },
      active: { filter: { type: 'none' } },
    },
  }
})
</script>

<template>
  <VCard>
    <VCardItem class="pb-3">
      <VCardTitle class="text-no-wrap">
        {{ props.title ?? 'Договоры' }}
      </VCardTitle>
    </VCardItem>

    <VCardText class="d-flex align-start justify-space-between flex-wrap gap-4">
      <div class="d-flex align-start gap-6 flex-wrap">
        <div class="d-flex flex-column">
          <div class="text-body-2 text-medium-emphasis mb-1">
            {{ props.currentMonthLabel }}
          </div>
          <div class="text-h4 mb-1">
            {{ formatSum(props.currentMonthSum ?? 0) }} {{ currency }}
          </div>
          <VChip
            label
            color="primary"
            size="small"
            variant="tonal"
          >
            Договоров: {{ props.currentMonthCount ?? 0 }}
          </VChip>
        </div>

        <div class="d-flex flex-column">
          <div class="text-body-2 text-medium-emphasis mb-1">
            {{ props.prevMonthLabel }}
          </div>
          <div class="text-h4 mb-1">
            {{ formatSum(props.prevMonthSum ?? 0) }} {{ currency }}
          </div>
          <VChip
            label
            color="info"
            size="small"
            variant="tonal"
          >
            Договоров: {{ props.prevMonthCount ?? 0 }}
          </VChip>
        </div>
      </div>

      <div class="flex-shrink-0">
        <VueApexCharts
          :options="chartOptions"
          :series="series"
          :height="178"
        />
      </div>
    </VCardText>
  </VCard>
</template>
