<script setup lang="ts">
import { useTheme } from 'vuetify'
import { hexToRgb } from '@layouts/utils'

const props = defineProps<{
  labels: string[]
  weekCounts: number[]
  monthCount: number
  monthSeconds: number
}>()

const vuetifyTheme = useTheme()

const series = computed(() => [{ data: props.weekCounts ?? [] }])

const chartOptions = computed(() => {
  const currentTheme = vuetifyTheme.current.value.colors
  const variableTheme = vuetifyTheme.current.value.variables

  const labelColor = `rgba(${hexToRgb(currentTheme['on-surface'])},${variableTheme['disabled-opacity']})`
  const soft = `rgba(${hexToRgb(currentTheme.warning)},0.18)`
  const solid = `rgba(${hexToRgb(currentTheme.warning)},1)`
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
  }
})

const durationText = computed(() => {
  const seconds = Math.max(0, Number(props.monthSeconds ?? 0) || 0)
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  return `${hours}ч ${minutes}м`
})
</script>

<template>
  <VCard>
    <VCardText class="d-flex justify-space-between gap-4">
      <div class="d-flex flex-column">
        <div class="mb-auto">
          <h5 class="text-h5 text-no-wrap mb-2">
            Сметы и активность
          </h5>
          <div class="text-body-1">
            Текущий месяц
          </div>
        </div>

        <div class="d-flex flex-column gap-2">
          <VChip
            label
            color="warning"
            size="small"
            variant="tonal"
          >
            Смет создано: {{ props.monthCount ?? 0 }}
          </VChip>
          <VChip
            label
            color="secondary"
            size="small"
            variant="tonal"
          >
            В системе: {{ durationText }}
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

