<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { $api } from '@/utils/api'
import { useDictionariesStore } from '@/stores/dictionaries'
import { formatSum } from '@/utils/formatters/finance'
import AppDateTimePicker from '@/@core/components/app-form-elements/AppDateTimePicker.vue'
import AppSelect from '@/@core/components/app-form-elements/AppSelect.vue'
import CashboxCell from '@/components/cashboxes/CashboxCell.vue'
import type { CashflowReport, CashflowReportSection } from '@/types/finance'

const dictionaries = useDictionariesStore()
const loading = ref(false)
const errorMessage = ref('')
const report = ref<CashflowReport | null>(null)

const datePickerConfig = {
  altInput: true,
  altFormat: 'd.m.Y',
  dateFormat: 'Y-m-d',
  allowInput: true,
  clickOpens: true,
}

const today = new Date()
const monthStart = new Date(today.getFullYear(), today.getMonth(), 1)

const formatDate = (value: Date) => value.toISOString().slice(0, 10)

const filters = reactive({
  company_id: null as number | null,
  cashbox_id: null as number | null,
  date_from: formatDate(monthStart),
  date_to: formatDate(today),
  group_by: null as 'day' | 'week' | 'month' | null,
})

const groupOptions = [
  { label: 'Без группировки', value: null },
  { label: 'По дням', value: 'day' },
  { label: 'По неделям', value: 'week' },
  { label: 'По месяцам', value: 'month' },
]

const sectionLabels: Record<string, string> = {
  OPERATING: 'Операционная деятельность',
  INVESTING: 'Инвестиционная деятельность',
  FINANCING: 'Финансовая деятельность',
}

const normalizeDateValue = (value?: string | null) => {
  const trimmed = (value ?? '').trim()
  if (!trimmed) return ''
  const match = trimmed.match(/^(\d{2})\.(\d{2})\.(\d{4})$/)
  if (match) {
    const [, day, month, year] = match
    return `${year}-${month}-${day}`
  }
  return trimmed
}

const sections = computed<CashflowReportSection[]>(() => report.value?.rows ?? [])
const timeline = computed(() => report.value?.timeline ?? [])

const loadReport = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const params: Record<string, any> = {
      date_from: normalizeDateValue(filters.date_from),
      date_to: normalizeDateValue(filters.date_to),
    }
    if (filters.company_id) params.company_id = filters.company_id
    if (filters.cashbox_id) params.cashbox_id = filters.cashbox_id
    if (filters.group_by) params.group_by = filters.group_by

    const response: any = await $api('reports/cashflow', { params })
    report.value = response?.data?.data ?? response?.data ?? response ?? null
  } catch (error: any) {
    errorMessage.value =
      error?.data?.message ?? error?.response?.data?.message ?? 'Не удалось загрузить отчёт.'
    report.value = null
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await Promise.all([dictionaries.loadCompanies(), dictionaries.loadCashBoxes(true)])
  if (!filters.company_id && dictionaries.companies.length === 1) {
    filters.company_id = Number(dictionaries.companies[0].id)
  }
  await loadReport()
})
</script>

<template>
  <div class="d-flex flex-column gap-4">
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <span>ОДДС</span>
        <VBtn color="primary" variant="tonal" :loading="loading" @click="loadReport">Обновить</VBtn>
      </VCardTitle>
      <VCardText>
        <div v-if="errorMessage" class="text-sm mb-3" style="color: #b91c1c;">
          {{ errorMessage }}
        </div>

        <VRow dense>
          <VCol cols="12" md="3">
            <AppSelect
              v-model="filters.company_id"
              :items="dictionaries.companies"
              item-title="name"
              item-value="id"
              label="Компания"
              clearable
            />
          </VCol>
          <VCol cols="12" md="3">
            <AppSelect
              v-model="filters.cashbox_id"
              :items="dictionaries.cashBoxes"
              item-title="name"
              item-value="id"
              label="Касса"
              clearable
            >
              <template #selection="{ item }">
                <CashboxCell :cashbox="item?.raw ?? item" size="sm" />
              </template>
              <template #item="{ props: itemProps, item }">
                <VListItem v-bind="itemProps">
                  <CashboxCell :cashbox="item?.raw ?? item" size="sm" />
                </VListItem>
              </template>
            </AppSelect>
          </VCol>
          <VCol cols="12" md="2">
            <AppDateTimePicker
              v-model="filters.date_from"
              label="Период с"
              placeholder="ДД.ММ.ГГГГ"
              :config="datePickerConfig"
              hide-details
            />
          </VCol>
          <VCol cols="12" md="2">
            <AppDateTimePicker
              v-model="filters.date_to"
              label="по"
              placeholder="ДД.ММ.ГГГГ"
              :config="datePickerConfig"
              hide-details
            />
          </VCol>
          <VCol cols="12" md="2">
            <AppSelect
              v-model="filters.group_by"
              :items="groupOptions"
              item-title="label"
              item-value="value"
              label="Группировка"
            />
          </VCol>
        </VRow>

        <div class="d-flex justify-end mt-3">
          <VBtn color="primary" :loading="loading" @click="loadReport">Показать</VBtn>
        </div>
      </VCardText>
    </VCard>

    <VCard v-if="report?.summary">
      <VCardTitle>Сводка</VCardTitle>
      <VCardText>
        <div class="d-flex flex-wrap gap-6">
          <div>Остаток на начало: {{ formatSum(report.summary.opening_balance) }}</div>
          <div>Поступления: {{ formatSum(report.summary.inflow) }}</div>
          <div>Расходы: {{ formatSum(report.summary.outflow) }}</div>
          <div>Сальдо: {{ formatSum(report.summary.net) }}</div>
          <div>Остаток на конец: {{ formatSum(report.summary.closing_balance) }}</div>
        </div>
      </VCardText>
    </VCard>

    <VCard>
      <VCardTitle>Движение по статьям</VCardTitle>
      <VCardText>
        <div v-if="!sections.length" class="text-muted text-sm">Нет данных за период.</div>
        <div v-for="section in sections" :key="section.section" class="mb-6">
          <div class="text-subtitle-1 mb-2">
            {{ sectionLabels[section.section] ?? section.section }}
          </div>
          <VTable class="text-no-wrap">
            <thead>
              <tr>
                <th>Статья</th>
                <th class="text-right">Поступления</th>
                <th class="text-right">Расходы</th>
                <th class="text-right">Сальдо</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in section.items" :key="item.id">
                <td>{{ [item.code, item.name].filter(Boolean).join(' — ') }}</td>
                <td class="text-right">{{ formatSum(item.amount_in) }}</td>
                <td class="text-right">{{ formatSum(item.amount_out) }}</td>
                <td class="text-right">{{ formatSum(item.net) }}</td>
              </tr>
              <tr class="font-weight-bold">
                <td>Итого</td>
                <td class="text-right">{{ formatSum(section.totals.in) }}</td>
                <td class="text-right">{{ formatSum(section.totals.out) }}</td>
                <td class="text-right">{{ formatSum(section.totals.net) }}</td>
              </tr>
            </tbody>
          </VTable>
        </div>
      </VCardText>
    </VCard>

    <VCard v-if="timeline?.length">
      <VCardTitle>Динамика</VCardTitle>
      <VCardText>
        <VTable class="text-no-wrap">
          <thead>
            <tr>
              <th>Период</th>
              <th class="text-right">Поступления</th>
              <th class="text-right">Расходы</th>
              <th class="text-right">Сальдо</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in timeline" :key="row.period">
              <td>{{ row.period }}</td>
              <td class="text-right">{{ formatSum(row.inflow) }}</td>
              <td class="text-right">{{ formatSum(row.outflow) }}</td>
              <td class="text-right">{{ formatSum(row.net) }}</td>
            </tr>
          </tbody>
        </VTable>
      </VCardText>
    </VCard>
  </div>
</template>
