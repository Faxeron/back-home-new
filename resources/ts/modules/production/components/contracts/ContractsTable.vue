<script setup lang="ts">
import { computed } from 'vue'

import DataTable from 'primevue/datatable'

import Column from 'primevue/column'

import InputText from 'primevue/inputtext'

import Select from 'primevue/select'

import Button from 'primevue/button'

import { CONTRACT_COLUMNS } from '@/modules/production/config/contractsTable.config'
import { formatDateShort, formatSum } from '@/utils/formatters/finance'

import type { Contract, ContractStatus } from '@/types/finance'


const EMPTY_TEXT = '\u2014'
const EMPTY_MESSAGE = '\u041d\u0435\u0442 \u0434\u0430\u043d\u043d\u044b\u0445.'
const LOADING_MESSAGE = '\u0417\u0430\u0433\u0440\u0443\u0437\u043a\u0430...'
const TOOLTIP_LABELS = {
  contract: '\u041a\u0430\u0440\u0442\u043e\u0447\u043a\u0430',
  acts: '\u0410\u043a\u0442\u044b',
  addReceipt: '\u0414\u043e\u0431\u0430\u0432\u0438\u0442\u044c \u043f\u0440\u0438\u0445\u043e\u0434',
  addSpending: '\u0414\u043e\u0431\u0430\u0432\u0438\u0442\u044c \u0440\u0430\u0441\u0445\u043e\u0434',
  remove: '\u0423\u0434\u0430\u043b\u0438\u0442\u044c',
}


const props = defineProps<{

  rows: Contract[]

  loading: boolean

  totalRecords: number

  scrollHeight: string

  virtualScrollerOptions: Record<string, any>

  search: string

  statusId: number | null

  statuses: ContractStatus[]

  canDelete: boolean

  canEditStatus: boolean

  canFinance: boolean

}>()



const emit = defineEmits<{

  (e: 'update:search', value: string): void

  (e: 'update:statusId', value: number | null): void

  (e: 'status-change', payload: { row: Contract; statusId: number | null }): void

  (e: 'action', payload: { action: string; row: Contract }): void

  (e: 'reset'): void

}>()



const searchModel = computed({

  get: () => props.search,

  set: value => emit('update:search', value),

})



const totalLabel = computed(() => Number(props.totalRecords ?? 0).toLocaleString('ru-RU'))

const hasFilters = computed(() => !!props.search || props.statusId !== null)



const statusMap = computed(() => {

  const map = new Map<number, ContractStatus>()

  for (const status of props.statuses || []) {

    if (status?.id != null) map.set(Number(status.id), status)

  }

  return map

})



const formatMoney = (value?: number | null) => {

  if (value === null || value === undefined) return EMPTY_TEXT

  return formatSum(value)

}

const isFullyPaid = (row: Contract) => {

  const total = Number(row?.total_amount ?? 0)

  const paid = Number(row?.paid_amount ?? 0)

  return total > 0 && paid >= total

}





const getStatus = (statusId?: number | null) => {

  if (statusId === null || statusId === undefined) return null

  return statusMap.value.get(Number(statusId)) ?? null

}



const getStatusColor = (statusId?: number | null) => getStatus(statusId)?.color ?? '#94a3b8'



const getTextColor = (hexColor: string) => {

  const hex = hexColor.replace('#', '')

  if (hex.length !== 6) return '#111827'

  const r = parseInt(hex.slice(0, 2), 16)

  const g = parseInt(hex.slice(2, 4), 16)

  const b = parseInt(hex.slice(4, 6), 16)

  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255

  return luminance > 0.6 ? '#111827' : '#ffffff'

}



const statusButtonStyle = (status: ContractStatus, isActive: boolean) => {

  const color = status.color ?? '#94a3b8'

  return isActive

    ? {

        backgroundColor: color,

        borderColor: color,

        color: getTextColor(color),

      }

    : {

        borderColor: color,

        color,

      }

}



const handleStatusToggle = (nextId: number) => {

  const nextValue = props.statusId === nextId ? null : nextId

  emit('update:statusId', nextValue)

}

</script>



<template>

  <DataTable

    :value="rows"

    dataKey="id"

    class="p-datatable-sm"

    :loading="loading"

    :totalRecords="totalRecords"
    scrollable
    :scrollHeight="scrollHeight"
    :virtualScrollerOptions="virtualScrollerOptions"
    :emptyMessage="EMPTY_MESSAGE"
    :loadingMessage="LOADING_MESSAGE"
    :sortField="CONTRACT_COLUMNS.id.field"
    :sortOrder="-1"
    lazy
    stripedRows
  >

    <template #header>

      <div class="flex flex-col gap-3">

        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <InputText
              v-model="searchModel"
              class="w-64"
              placeholder="Поиск по ID, клиенту, модели"
            />
            <Button
              v-if="searchModel"
              icon="pi pi-times"
              text
              aria-label="Очистить поиск"
              @click="emit('update:search', '')"
            />
          </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Button
            v-for="status in statuses"
            :key="status.id"
            :label="status.name"
            size="small"
            :outlined="statusId !== Number(status.id)"
            :style="statusButtonStyle(status, statusId === Number(status.id))"
            @click="handleStatusToggle(Number(status.id))"
          />
          <div class="flex items-center gap-3 ml-auto">
            <Button
              label="Сброс фильтров"
              size="small"
              text
              icon="pi pi-refresh"
              :disabled="!hasFilters"
              @click="emit('reset')"
            />
            <TableTotalLabel label="Всего" :value="totalLabel" />
            <RouterLink
              to="/operations/contracts/history"
              custom
              v-slot="{ navigate }"
            >
              <Button
                label="История"
                size="small"
                text
                @click="navigate"
              />
            </RouterLink>
          </div>
        </div>
      </div>
    </template>


    <Column

      :field="CONTRACT_COLUMNS.id.field"

      :header="CONTRACT_COLUMNS.id.header"

      style="inline-size: 6ch;"

    >

      <template #body="{ data }">

        {{ data.id ?? EMPTY_TEXT }}

      </template>

    </Column>



    <Column

      :field="CONTRACT_COLUMNS.counterparty.field"

      :header="CONTRACT_COLUMNS.counterparty.header"

    >

      <template #body="{ data }">

        {{ data.counterparty?.name ?? EMPTY_TEXT }}

      </template>

    </Column>



    <Column

      :field="CONTRACT_COLUMNS.address.field"

      :header="CONTRACT_COLUMNS.address.header"

    >

      <template #body="{ data }">

        {{ data.address ?? EMPTY_TEXT }}

      </template>

    </Column>



    <Column

      :field="CONTRACT_COLUMNS.model.field"

      :header="CONTRACT_COLUMNS.model.header"

    >

      <template #body>

        {{ EMPTY_TEXT }}

      </template>

    </Column>



    <Column

      :field="CONTRACT_COLUMNS.estimate.field"

      :header="CONTRACT_COLUMNS.estimate.header"

    >

      <template #body>

        {{ EMPTY_TEXT }}

      </template>

    </Column>



    <Column

      :field="CONTRACT_COLUMNS.workDates.field"

      :header="CONTRACT_COLUMNS.workDates.header"

    >

      <template #body="{ data }">

        <div class="leading-tight py-1">

          <div>{{ formatDateShort(data.work_start_date) }}</div>

          <div class="text-muted text-xs">{{ formatDateShort(data.work_end_date) }}</div>

        </div>

      </template>

    </Column>



    <Column

      :field="CONTRACT_COLUMNS.saleType.field"

      :header="CONTRACT_COLUMNS.saleType.header"

    >

      <template #body="{ data }">

        {{ data.sale_type?.name ?? EMPTY_TEXT }}

      </template>

    </Column>



    <Column

      :field="CONTRACT_COLUMNS.totalAmount.field"

      :header="CONTRACT_COLUMNS.totalAmount.header"

    >

      <template #body="{ data }">

        <span :class="isFullyPaid(data) ? 'contract-paid' : ''">
          {{ formatMoney(data.total_amount) }}
        </span>

      </template>

    </Column>



    <Column

      :field="CONTRACT_COLUMNS.paidDebt.field"

      :header="CONTRACT_COLUMNS.paidDebt.header"

    >

      <template #body="{ data }">

        <div :class="['leading-tight', 'py-1', isFullyPaid(data) ? 'contract-paid' : '']">

          <div>{{ formatMoney(data.paid_amount) }}</div>

          <div class="text-muted text-xs">{{ formatMoney(data.debt) }}</div>

        </div>

      </template>

    </Column>



    <Column

      :field="CONTRACT_COLUMNS.staff.field"

      :header="CONTRACT_COLUMNS.staff.header"

    >

      <template #body="{ data }">

        <div class="leading-tight py-1">

          <div>{{ data.manager?.name ?? EMPTY_TEXT }}</div>

          <div class="text-muted text-xs">{{ data.measurer?.name ?? EMPTY_TEXT }}</div>

        </div>

      </template>

    </Column>



    <Column

      :field="CONTRACT_COLUMNS.status.field"

      :header="CONTRACT_COLUMNS.status.header"

    >

      <template #body="{ data }">

        <Select

          :modelValue="data.contract_status_id ?? null"

          :options="statuses"

          optionLabel="name"

          optionValue="id"

          class="w-full"
          :disabled="!canEditStatus"

          @update:modelValue="

            emit('status-change', { row: data, statusId: $event != null ? Number($event) : null })

          "

        >

          <template #value="{ value }">

            <div

              v-if="value"

              class="flex items-center gap-2"

            >

              <span

                class="status-dot"

                :style="{ backgroundColor: getStatusColor(value) }"

              />

              <span>{{ getStatus(value)?.name ?? EMPTY_TEXT }}</span>

            </div>

            <span v-else class="text-muted">{{ EMPTY_TEXT }}</span>

          </template>

          <template #option="{ option }">

            <div class="flex items-center gap-2">

              <span

                class="status-dot"

                :style="{ backgroundColor: option?.color ?? '#94a3b8' }"

              />

              <span>{{ option?.name ?? '' }}</span>

            </div>

          </template>

        </Select>

      </template>

    </Column>
    <Column
      :field="CONTRACT_COLUMNS.actions.field"
      :header="CONTRACT_COLUMNS.actions.header"
      style="inline-size: 18ch;"
    >
      <template #body="{ data }">
        <div class="flex flex-column gap-1">
          <div class="flex items-center gap-1">
            <VTooltip v-if="canFinance" location="top" content-class="text-body-2">
              <template #activator="{ props: tooltipProps }">
                <Button
                  v-bind="tooltipProps"
                  icon="pi pi-file"
                  text
                  @click="emit('action', { action: 'contract', row: data })"
                />
              </template>
              <span>{{ TOOLTIP_LABELS.contract }}</span>
            </VTooltip>
            <VTooltip v-if="canFinance" location="top" content-class="text-body-2">
              <template #activator="{ props: tooltipProps }">
                <Button
                  v-bind="tooltipProps"
                  icon="pi pi-file-edit"
                  text
                  @click="emit('action', { action: 'act', row: data })"
                />
              </template>
              <span>{{ TOOLTIP_LABELS.acts }}</span>
            </VTooltip>
          </div>
          <div class="flex items-center gap-1">
            <VTooltip location="top" content-class="text-body-2">
              <template #activator="{ props: tooltipProps }">
                <Button
                  v-bind="tooltipProps"
                  icon="pi pi-plus"
                  text
                  @click="emit('action', { action: 'receipt', row: data })"
                />
              </template>
              <span>{{ TOOLTIP_LABELS.addReceipt }}</span>
            </VTooltip>
            <VTooltip location="top" content-class="text-body-2">
              <template #activator="{ props: tooltipProps }">
                <Button
                  v-bind="tooltipProps"
                  icon="pi pi-minus"
                  text
                  @click="emit('action', { action: 'spending', row: data })"
                />
              </template>
              <span>{{ TOOLTIP_LABELS.addSpending }}</span>
            </VTooltip>
            <VTooltip v-if="canDelete" location="top" content-class="text-body-2">
              <template #activator="{ props: tooltipProps }">
                <Button
                  v-bind="tooltipProps"
                  icon="pi pi-trash"
                  text
                  @click="emit('action', { action: 'delete', row: data })"
                />
              </template>
              <span>{{ TOOLTIP_LABELS.remove }}</span>
            </VTooltip>
          </div>
        </div>
      </template>
    </Column>

    <template #empty>
      <div class="text-center py-6 text-muted">{{ EMPTY_MESSAGE }}</div>
    </template>


    <template #loading>
      <div class="text-center py-6 text-muted">{{ LOADING_MESSAGE }}</div>
    </template>
  </DataTable>

</template>



<style scoped>

.status-dot {

  inline-size: 0.65rem;

  block-size: 0.65rem;

  border-radius: 999px;

  display: inline-block;

}

.contract-paid {
  color: rgb(var(--v-theme-success));
  font-weight: 600;
}

.contract-paid .text-muted {
  color: rgb(var(--v-theme-success)) !important;
}

</style>







