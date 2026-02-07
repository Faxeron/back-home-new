<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { $api } from '@/utils/api'
import EmployeeContractsMonth from '@/views/dashboards/employee/EmployeeContractsMonth.vue'
import EmployeeContractsSalaryMonth from '@/views/dashboards/employee/EmployeeContractsSalaryMonth.vue'
import EmployeeEstimatesActivityMonth from '@/views/dashboards/employee/EmployeeEstimatesActivityMonth.vue'
import EmployeePayrollMonth from '@/views/dashboards/employee/EmployeePayrollMonth.vue'

definePage({
  meta: {
    action: 'view',
    subject: 'dashboard.employee',
  },
})

type SummaryResponse = {
  data: {
    period: { from: string; to: string }
    contracts: {
      all_time: { count: number; sum: number; currency: string }
      month: { count: number; sum: number; currency: string }
      prev_month: { count: number; sum: number; currency: string }
      series: { labels: string[]; counts: number[]; sums: number[] }
      prev_series: { labels: string[]; counts: number[]; sums: number[] }
    }
    payroll: {
      month: { count: number; accrued_sum: number; currency: string }
      series: { labels: string[]; sums: number[] }
    }
    estimates: {
      month: { count: number }
      series: { labels: string[]; counts: number[] }
    }
    activity: {
      month: { seconds: number }
    }
  }
}

const loading = ref(false)
const error = ref('')
const summary = ref<SummaryResponse['data'] | null>(null)

const monthLabelYY = (date: Date) => {
  const month = new Intl.DateTimeFormat('ru-RU', { month: 'long' }).format(date)
  const yy = String(date.getFullYear()).slice(-2)
  const text = `${month} ${yy}`
  return text.charAt(0).toUpperCase() + text.slice(1)
}

const weekIndexLabels = (count: number) => Array.from({ length: count }, (_, i) => `Нед ${i + 1}`)
const pad = (arr: number[], len: number) => (arr.length >= len ? arr : [...arr, ...Array.from({ length: len - arr.length }, () => 0)])

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const resp: any = await $api('dashboards/employee/summary')
    summary.value = resp?.data ?? null
  } catch (e: any) {
    summary.value = null
    error.value = e?.response?.data?.message ?? 'Не удалось загрузить дашборд сотрудника.'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <VRow class="match-height">
    <VCol cols="12">
      <div class="d-flex align-center justify-space-between flex-wrap gap-3">
        <div>
          <h4 class="text-h4 mb-1">
            Дашборды сотрудника
          </h4>
          <div class="text-medium-emphasis">
            Личная статистика по договорам, сметам, зарплате и активности
          </div>
        </div>

        <div class="d-flex align-center gap-2">
          <VBtn
            variant="tonal"
            color="primary"
            :loading="loading"
            @click="load"
          >
            Обновить
          </VBtn>
        </div>
      </div>
    </VCol>

    <VCol cols="12">
      <VAlert
        v-if="error"
        type="error"
        variant="tonal"
      >
        {{ error }}
      </VAlert>
    </VCol>

    <VCol
      cols="12"
      lg="6"
    >
      <EmployeeContractsMonth
        :labels="weekIndexLabels(Math.max(summary?.contracts.series.counts?.length ?? 0, summary?.contracts.prev_series.counts?.length ?? 0))"
        :current-week-counts="pad(summary?.contracts.series.counts ?? [], Math.max(summary?.contracts.series.counts?.length ?? 0, summary?.contracts.prev_series.counts?.length ?? 0))"
        :prev-week-counts="pad(summary?.contracts.prev_series.counts ?? [], Math.max(summary?.contracts.series.counts?.length ?? 0, summary?.contracts.prev_series.counts?.length ?? 0))"
        :current-month-count="summary?.contracts.month.count ?? 0"
        :current-month-sum="summary?.contracts.month.sum ?? 0"
        :prev-month-count="summary?.contracts.prev_month.count ?? 0"
        :prev-month-sum="summary?.contracts.prev_month.sum ?? 0"
        :current-month-label="monthLabelYY(new Date())"
        :prev-month-label="monthLabelYY(new Date(new Date().getFullYear(), new Date().getMonth() - 1, 1))"
        :currency="summary?.contracts.month.currency ?? summary?.contracts.prev_month.currency ?? 'RUB'"
        title="Заключено договоров"
        subtitle="Текущий месяц"
      />
    </VCol>

    <VCol
      cols="12"
      lg="6"
    >
      <EmployeeContractsSalaryMonth
        :labels="summary?.payroll.series.labels ?? summary?.contracts.series.labels ?? []"
        :week-salary-sums="summary?.payroll.series.sums ?? []"
        :contracts-count="summary?.contracts.month.count ?? 0"
        :contracts-sum="summary?.contracts.month.sum ?? 0"
        :salary-accrued-sum="summary?.payroll.month.accrued_sum ?? 0"
        :currency="summary?.payroll.month.currency ?? 'RUB'"
      />
    </VCol>

    <VCol
      cols="12"
      lg="6"
    >
      <EmployeeEstimatesActivityMonth
        :labels="summary?.estimates.series.labels ?? []"
        :week-counts="summary?.estimates.series.counts ?? []"
        :month-count="summary?.estimates.month.count ?? 0"
        :month-seconds="summary?.activity.month.seconds ?? 0"
      />
    </VCol>

    <VCol
      cols="12"
      lg="6"
    >
      <EmployeePayrollMonth
        :labels="summary?.payroll.series.labels ?? []"
        :week-sums="summary?.payroll.series.sums ?? []"
        :month-accrued-sum="summary?.payroll.month.accrued_sum ?? 0"
        :month-contracts-count="summary?.contracts.month.count ?? 0"
        :currency="summary?.payroll.month.currency ?? 'RUB'"
      />
    </VCol>
  </VRow>
</template>
