<script setup lang="ts">
import { receiptsListEndpoint } from '@/api/finance/receipts'
import type { Receipt } from '@/types/finance'

const page = ref(1)
const itemsPerPage = ref(25)

const perPageOptions = [
  { title: '10', value: 10 },
  { title: '25', value: 25 },
  { title: '50', value: 50 },
  { title: '100', value: 100 },
]

const headers = [
  { title: 'ID', key: 'id', sortable: true, width: 60 },
  { title: 'Дата оплаты', key: 'payment_date', sortable: true },
  { title: 'Транзакция', key: 'transaction_id' },
  { title: 'Касса', key: 'cash_box_id' },
  { title: 'Компания', key: 'company_id' },
  { title: 'Контрагент', key: 'counterparty_id' },
  { title: 'Договор', key: 'contract_id' },
  { title: 'Сумма', key: 'sum', sortable: true },
  { title: 'Описание', key: 'description' },
  { title: 'Действия', key: 'actions', sortable: false, width: 64 },
]

const filters = reactive({
  dateFrom: null as string | null,
  dateTo: null as string | null,
  cashBoxId: null as number | null,
  contractId: null as number | null,
  counterpartyId: null as number | null,
  companyId: null as number | null,
  search: '',
})

const include = 'cashBox,company,counterparty,contract,transaction'

const endpoint = receiptsListEndpoint({
  page,
  per_page: itemsPerPage,
  date_from: computed(() => filters.dateFrom || undefined),
  date_to: computed(() => filters.dateTo || undefined),
  cash_box_id: computed(() => filters.cashBoxId || undefined),
  contract_id: computed(() => filters.contractId || undefined),
  counterparty_id: computed(() => filters.counterpartyId || undefined),
  company_id: computed(() => filters.companyId || undefined),
  search: computed(() => filters.search || undefined),
  include,
})

const {
  data: response,
  execute: fetchReceipts,
  isFetching,
} = await useApi<{ data: Receipt[]; meta: any }>(endpoint)

const receipts = computed(() => response.value?.data ?? [])
const pagination = computed(() => response.value?.meta ?? { total: 0, per_page: itemsPerPage.value })

watch(itemsPerPage, () => {
  page.value = 1
})

watch([page, itemsPerPage], () => {
  fetchReceipts()
})

watch(filters, () => {
  page.value = 1
  fetchReceipts()
}, { deep: true })

const formatCurrency = (money: Receipt['sum']) => {
  if (!money)
    return '-'
  const number = Number(money.amount ?? 0)
  return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: money.currency || 'RUB', maximumFractionDigits: 2 }).format(number)
}

const formatDate = (value?: string) => value ? new Date(value).toLocaleDateString('ru-RU') : '-'
const formatCompany = (item: Receipt) => item?.company?.name || item?.company_id || '-'
const formatCashBox = (item: Receipt) => item?.cash_box?.name || item?.cash_box_id || '-'
const formatCounterparty = (item: Receipt) => item?.counterparty?.name || item?.counterparty_id || '-'
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardTitle class="d-flex flex-column align-start py-4">
          <div class="text-h5">
            Приходы (receipts)
          </div>
          <VCardSubtitle class="text-wrap">
            Список поступлений
          </VCardSubtitle>
        </VCardTitle>

        <VDivider />

        <VCardText>
          <div class="d-flex flex-wrap gap-4 mb-4 align-center">
            <AppTextField
              v-model="filters.search"
              prepend-inner-icon="tabler-search"
              placeholder="Поиск по описанию"
              style="inline-size: 18rem;"
              clearable
            />
            <AppDateTimePicker
              v-model="filters.dateFrom"
              label="Дата с"
              prepend-inner-icon="tabler-calendar"
              placeholder="дд.мм.гггг"
              density="compact"
              clearable
              class="ma-0"
              style="inline-size: 10rem;"
            />
            <AppDateTimePicker
              v-model="filters.dateTo"
              label="Дата по"
              prepend-inner-icon="tabler-calendar"
              placeholder="дд.мм.гггг"
              density="compact"
              clearable
              class="ma-0"
              style="inline-size: 10rem;"
            />
            <AppTextField
              v-model.number="filters.cashBoxId"
              label="Касса (ID)"
              type="number"
              density="compact"
              style="inline-size: 8rem;"
              clearable
            />
            <AppTextField
              v-model.number="filters.companyId"
              label="Компания (ID)"
              type="number"
              density="compact"
              style="inline-size: 8rem;"
              clearable
            />
            <AppTextField
              v-model.number="filters.contractId"
              label="Договор (ID)"
              type="number"
              density="compact"
              style="inline-size: 8rem;"
              clearable
            />
            <AppTextField
              v-model.number="filters.counterpartyId"
              label="Контрагент (ID)"
              type="number"
              density="compact"
              style="inline-size: 8rem;"
              clearable
            />
          </div>

          <VDataTableServer
            v-model:page="page"
            v-model:items-per-page="itemsPerPage"
            :headers="headers"
            :items="receipts"
            :items-length="pagination.total ?? 0"
            :loading="isFetching"
            :items-per-page-options="perPageOptions"
            item-value="id"
            class="text-no-wrap"
            fixed-header
            height="650"
          >
            <template #item.id="{ item }">
              <span class="text-medium-emphasis text-caption" style="min-inline-size: 60px; display: inline-block;">{{ item.id }}</span>
            </template>

            <template #item.payment_date="{ item }">
              {{ formatDate(item.payment_date || item.created_at) }}
            </template>

            <template #item.cash_box_id="{ item }">
              {{ formatCashBox(item) }}
            </template>

            <template #item.transaction_id="{ item }">
              {{ item.transaction_id || '-' }}
            </template>

            <template #item.company_id="{ item }">
              {{ formatCompany(item) }}
            </template>

            <template #item.counterparty_id="{ item }">
              {{ formatCounterparty(item) }}
            </template>

            <template #item.sum="{ item }">
              <span class="text-success font-weight-medium">
                {{ formatCurrency(item.sum) }}
              </span>
            </template>

            <template #item.description="{ item }">
              {{ item.description || '-' }}
            </template>

            <template #item.actions="{ item }">
              <VMenu>
                <template #activator="{ props }">
                  <IconBtn v-bind="props">
                    <VIcon icon="tabler-dots" />
                  </IconBtn>
                </template>
                <VList>
                  <VListItem @click="console.log('edit', item.id)">
                    <VListItemTitle>Редактировать</VListItemTitle>
                  </VListItem>
                  <VListItem @click="console.log('delete', item.id)">
                    <VListItemTitle>Удалить</VListItemTitle>
                  </VListItem>
                </VList>
              </VMenu>
            </template>

            <template #bottom>
              <div class="d-flex flex-wrap align-center justify-space-between gap-4 px-4 py-3">
                <div class="d-flex align-center gap-2">
                  <span class="text-sm text-medium-emphasis">Записей на странице:</span>
                  <AppSelect
                    :model-value="itemsPerPage"
                    :items="perPageOptions"
                    hide-details
                    density="compact"
                    style="inline-size: 6.25rem;"
                    @update:model-value="itemsPerPage = Number($event)"
                  />
                </div>
                <TablePagination
                  v-model:page="page"
                  :items-per-page="itemsPerPage"
                  :total-items="pagination.total ?? 0"
                />
              </div>
            </template>
          </VDataTableServer>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>
