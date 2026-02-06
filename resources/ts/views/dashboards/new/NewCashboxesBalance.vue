<script setup lang="ts">
import { computed } from 'vue'
import { useTheme } from 'vuetify'
import { getBarChartConfig } from '@core/libs/apex-chart/apexCharConfig'
import { formatSum } from '@/utils/formatters/finance'
import CashboxCell from '@/components/cashboxes/CashboxCell.vue'

export type CashboxBalanceRow = {
  id: number
  name?: string | null
  balance?: number | null
  logo_url?: string | null
}

const props = defineProps<{
  rows: CashboxBalanceRow[]
  total: number
  loading?: boolean
  error?: string
}>()

const emit = defineEmits<{
  (e: 'refresh'): void
}>()

const theme = useTheme()

const sorted = computed(() =>
  [...(props.rows ?? [])]
    .sort((a, b) => (Number(b.balance ?? 0) || 0) - (Number(a.balance ?? 0) || 0)),
)

const topForChart = computed(() => sorted.value.slice(0, 8))

const chartHeight = computed(() => {
  // Keep it readable: each bar ~36px, with a sensible min/max.
  const bars = Math.max(3, topForChart.value.length || 0)
  return Math.min(420, Math.max(220, bars * 36))
})

const chartSeries = computed(() => [
  {
    data: topForChart.value.map(r => Number(r.balance ?? 0) || 0),
  },
])

const chartKey = computed(() =>
  topForChart.value
    .map(r => `${r.id}:${Number(r.balance ?? 0) || 0}`)
    .join('|'),
)

const chartOptions = computed(() => {
  const base: any = getBarChartConfig(theme.current.value)
  const categories = topForChart.value.map(r => r.name ?? `#${r.id}`)

  return {
    ...base,
    xaxis: {
      ...(base.xaxis ?? {}),
      categories,
      labels: {
        ...((base.xaxis?.labels ?? {}) as any),
        formatter: (val: string) => formatSum(Number(val)),
      },
    },
    tooltip: {
      ...(base.tooltip ?? {}),
      y: {
        formatter: (val: number) => `${formatSum(val)} RUB`,
      },
    },
    plotOptions: {
      ...(base.plotOptions ?? {}),
      bar: {
        ...(base.plotOptions?.bar ?? {}),
        barHeight: '38%',
      },
    },
  }
})

const percentOfTotal = (value: unknown) => {
  const total = Number(props.total ?? 0) || 0
  if (total <= 0) return 0
  const n = Number(value ?? 0) || 0
  return Math.max(0, Math.min(100, (n / total) * 100))
}
</script>

<template>
  <VCard class="h-100">
    <VCardItem>
      <VCardTitle>Баланс касс</VCardTitle>
      <template #append>
        <div class="d-flex align-center gap-2">
          <VBtn
            size="small"
            variant="tonal"
            :to="{ path: '/finance/balance' }"
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

      <VRow class="match-height">
        <VCol
          cols="12"
          md="7"
        >
          <div v-if="topForChart.length > 0">
            <VueApexCharts
              :key="chartKey"
              type="bar"
              :height="chartHeight"
              :options="chartOptions"
              :series="chartSeries"
            />
          </div>
          <div
            v-else
            class="text-medium-emphasis text-sm d-flex align-center justify-center"
            style="min-height: 220px;"
          >
            Нет данных для графика.
          </div>
        </VCol>

        <VCol
          cols="12"
          md="5"
        >
          <VList class="card-list">
            <VListItem
              v-for="row in sorted"
              :key="row.id"
            >
              <VListItemTitle class="d-flex align-center justify-space-between gap-3">
                <CashboxCell :cashbox="row" size="sm" />
                <span class="text-high-emphasis font-weight-medium">
                  {{ formatSum(row.balance ?? 0) }} RUB
                </span>
              </VListItemTitle>

              <VListItemSubtitle class="mt-2">
                <VProgressLinear
                  :model-value="percentOfTotal(row.balance)"
                  height="6"
                  rounded
                  color="primary"
                  bg-color="surface-variant"
                />
              </VListItemSubtitle>
            </VListItem>
          </VList>

          <VDivider class="my-4" />
          <div class="d-flex align-center justify-space-between">
            <span class="text-medium-emphasis">Итого</span>
            <span class="text-high-emphasis font-weight-semibold">
              {{ formatSum(props.total) }} RUB
            </span>
          </div>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 14px;
}
</style>
