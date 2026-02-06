<script setup lang="ts">
import { useTheme } from 'vuetify'
import { hexToRgb } from '@layouts/utils'
import { formatSum } from '@/utils/formatters/finance'

const props = defineProps<{
  title?: string
  subtitle?: string
  labels: string[]
  weekCounts: number[]
  monthCount: number
  monthSum: number
  currency?: string
}>()

const vuetifyTheme = useTheme()

const series = computed(() => [{ data: props.weekCounts ?? [] }])

const chartOptions = computed(() => {
  const currentTheme = vuetifyTheme.current.value.colors
  const variableTheme = vuetifyTheme.current.value.variables

  const labelColor = `rgba(${hexToRgb(currentTheme['on-surface'])},${variableTheme['disabled-opacity']})`
  const soft = `rgba(${hexToRgb(currentTheme.primary)},0.18)`
  const solid = `rgba(${hexToRgb(currentTheme.primary)},1)`

  const colors = (props.weekCounts ?? []).map((_, idx, arr) => (idx === arr.length - 1 ? solid : soft))

  return {
    chart: {
      height: 162,
      type: 'bar',
      parentHeightOffset: 0,
      toolbar: { show: false },
    },
    plotOptions: {
      bar: {
        barHeight: '80%',
        columnWidth: '38%',
        startingShape: 'rounded',
        endingShape: 'rounded',
        borderRadius: 6,
        distributed: true,
      },
    },
    tooltip: { enabled: false },
    grid: {
      show: false,
      padding: { top: -20, bottom: -12, left: -10, right: 0 },
    },
    colors,
    dataLabels: { enabled: false },
    legend: { show: false },
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

const currency = computed(() => props.currency ?? 'RUB')
</script>

<template>
  <VCard>
    <VCardText class="d-flex justify-space-between gap-4">
      <div class="d-flex flex-column">
        <div class="mb-auto">
          <h5 class="text-h5 text-no-wrap mb-2">
            {{ props.title ?? 'Договоры' }}
          </h5>
          <div class="text-body-1">
            {{ props.subtitle ?? 'Текущий месяц' }}
          </div>
        </div>

        <div>
          <div class="text-h4 mb-1">
            {{ formatSum(props.monthSum ?? 0) }} {{ currency }}
          </div>
          <VChip
            label
            color="primary"
            size="small"
            variant="tonal"
          >
            Заключено: {{ props.monthCount ?? 0 }}
          </VChip>
        </div>
      </div>

      <div class="flex-shrink-0">
        <VueApexCharts
          :options="chartOptions"
          :series="series"
          :height="162"
        />
      </div>
    </VCardText>
  </VCard>
</template>

