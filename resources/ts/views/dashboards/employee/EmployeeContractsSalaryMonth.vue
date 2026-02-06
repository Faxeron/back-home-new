<script setup lang="ts">
import { useTheme } from 'vuetify'
import { hexToRgb } from '@layouts/utils'
import { formatSum } from '@/utils/formatters/finance'

const props = defineProps<{
  labels: string[]
  contractsCount: number
  contractsSum: number
  salaryAccruedSum: number
  currency?: string
  weekSalarySums: number[]
}>()

const vuetifyTheme = useTheme()
const currency = computed(() => props.currency ?? 'RUB')

const series = computed(() => [{ data: props.weekSalarySums ?? [] }])

const chartOptions = computed(() => {
  const currentTheme = vuetifyTheme.current.value.colors
  const variableTheme = vuetifyTheme.current.value.variables

  const labelColor = `rgba(${hexToRgb(currentTheme['on-background'])},${variableTheme['disabled-opacity']})`
  const soft = `rgba(${hexToRgb(currentTheme.success)},0.16)`
  const solid = `rgba(${hexToRgb(currentTheme.success)}, 1)`
  const colors = (props.weekSalarySums ?? []).map((_, idx, arr) => (idx === arr.length - 1 ? solid : soft))

  return {
    chart: {
      type: 'bar',
      toolbar: { show: false },
      parentHeightOffset: 0,
    },
    tooltip: { enabled: false },
    plotOptions: {
      bar: {
        barHeight: '60%',
        columnWidth: '60%',
        startingShape: 'rounded',
        endingShape: 'rounded',
        borderRadius: 4,
        distributed: true,
      },
    },
    grid: {
      show: false,
      padding: { top: -20, bottom: 0, left: -10, right: -10 },
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
        },
      },
    },
    yaxis: { labels: { show: false } },
  }
})

const items = computed(() => ([
  {
    avatarIcon: 'tabler-briefcase',
    avatarColor: 'primary',
    title: 'Договоров за месяц',
    subtitle: 'По вам (менеджер)',
    value: `${props.contractsCount ?? 0} шт`,
  },
  {
    avatarIcon: 'tabler-currency-ruble',
    avatarColor: 'info',
    title: 'Сумма договоров',
    subtitle: 'Текущий месяц',
    value: `${formatSum(props.contractsSum ?? 0)} ${currency.value}`,
  },
  {
    avatarIcon: 'tabler-cash',
    avatarColor: 'success',
    title: 'Зарплата начислено',
    subtitle: 'Текущий месяц',
    value: `${formatSum(props.salaryAccruedSum ?? 0)} ${currency.value}`,
  },
]))
</script>

<template>
  <VCard
    title="Договоры и зарплата"
    subtitle="Текущий месяц"
  >
    <VCardText>
      <VList class="card-list mb-5">
        <VListItem
          v-for="row in items"
          :key="row.title"
        >
          <template #prepend>
            <VAvatar
              rounded
              size="34"
              variant="tonal"
              :color="row.avatarColor"
              class="me-1"
            >
              <VIcon
                :icon="row.avatarIcon"
                size="22"
              />
            </VAvatar>
          </template>

          <VListItemTitle class="font-weight-medium me-4">
            {{ row.title }}
          </VListItemTitle>
          <VListItemSubtitle class="me-4">
            {{ row.subtitle }}
          </VListItemSubtitle>

          <template #append>
            <div class="text-body-2 font-weight-medium">
              {{ row.value }}
            </div>
          </template>
        </VListItem>
      </VList>

      <VueApexCharts
        :options="chartOptions"
        :series="series"
        :height="196"
      />
    </VCardText>
  </VCard>
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 1.1rem;
}
</style>

