<script setup lang="ts">
import { spendingsListEndpoint } from '@/api/finance/spendings'
import type { Spending } from '@/types/finance'

const page = ref(1)
const itemsPerPage = ref(25)

const perPageOptions = [
  { title: '10', value: 10 },
  { title: '25', value: 25 },
  { title: '50', value: 50 },
  { title: '100', value: 100 },
]

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Дата оплаты', key: 'payment_date', sortable: true },
  { title: 'Договор/контрагент', key: 'contract' },
  { title: 'Фонд / статья', key: 'fund_item' },
  { title: 'Описание', key: 'description' },
  { title: 'Действия', key: 'actions', sortable: false, width: 64 },
]

const filters = reactive({
  dateFrom: null as string | null,
  dateTo: null as string | null,
  fondId: null as number | null,
  spendingItemId: null as number | null,
  cashBoxId: null as number | null,
  companyId: null as number | null,
  contractId: null as number | null,
  counterpartyId: null as number | null,
  spentToUserId: null as number | null,
  search: '',
})

const include = 'cashBox,company,counterparty,contract,item,fund,transaction,spentToUser'

const endpoint = spendingsListEndpoint({
  page,
  per_page: itemsPerPage,
  date_from: computed(() => filters.dateFrom || undefined),
  date_to: computed(() => filters.dateTo || undefined),
  fond_id: computed(() => filters.fondId || undefined),
  spending_item_id: computed(() => filters.spendingItemId || undefined),
  cash_box_id: computed(() => filters.cashBoxId || undefined),
  company_id: computed(() => filters.companyId || undefined),
  contract_id: computed(() => filters.contractId || undefined),
  counterparty_id: computed(() => filters.counterpartyId || undefined),
  spent_to_user_id: computed(() => filters.spentToUserId || undefined),
  search: computed(() => filters.search || undefined),
  include,
})

const {
  data: response,
  execute: fetchSpendings,
  isFetching,
} = await useApi<{ data: Spending[]; meta: any }>(endpoint)

const spendings = computed(() => response.value?.data ?? [])
const pagination = computed(() => response.value?.meta ?? { total: 0, per_page: itemsPerPage.value })

watch(itemsPerPage, () => {
  page.value = 1
})

watch([page, itemsPerPage], () => {
  fetchSpendings()
})

watch(filters, () => {
  page.value = 1
  fetchSpendings()
}, { deep: true })

const formatCurrency = (money: Spending['sum']) => {
  if (!money)
    return '-'
  const number = Number(money.amount ?? 0)
  return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: money.currency || 'RUB', maximumFractionDigits: 2 }).format(number)
}

const formatDate = (value?: string) => value ? new Date(value).toLocaleDateString('ru-RU') : '-'
const formatCompany = (item: Spending) => item?.company?.name || item?.company_id || '-'
const formatCashBox = (item: Spending) => item?.cash_box?.name || item?.cash_box_id || '-'
const formatFund = (item: Spending) => item?.fund?.name || item?.fond_id || '-'
const formatItem = (item: Spending) => item?.item?.name || item?.spending_item_id || '-'
const formatCounterparty = (item: Spending) => item?.counterparty?.name || item?.counterparty_id || item?.spent_to_user_id || '-'
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardTitle class="d-flex flex-column align-start py-4">
          <div class="text-h5">
            Расходы (spendings)
          </div>
          <VCardSubtitle class="text-wrap">
            Таблица расходов
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
              v-model.number="filters.fondId"
              label="Фонд (ID)"
              type="number"
              density="compact"
              style="inline-size: 8rem;"
              clearable
            />
            <AppTextField
              v-model.number="filters.spendingItemId"
              label="Статья (ID)"
              type="number"
              density="compact"
              style="inline-size: 8rem;"
              clearable
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
              label="Контрагент/Сотрудник (ID)"
              type="number"
              density="compact"
              style="inline-size: 10rem;"
              clearable
            />
          </div>

          <VDataTableServer
            v-model:page="page"
            v-model:items-per-page="itemsPerPage"
            :headers="headers"
            :items="spendings"
            :items-length="pagination.total ?? 0"
            :loading="isFetching"
            :items-per-page-options="perPageOptions"
            item-value="id"
            class="text-no-wrap"
            fixed-header
            height="650"
            hover
          >
            <template #item.id="{ item }">
              <div class="d-flex flex-column">
                <span class="text-medium-emphasis text-caption">{{ item.id }}</span>
                <span class="text-medium-emphasis text-caption">Tx: {{ item.transaction_id || '-' }}</span>
              </div>
            </template>

            <template #item.payment_date="{ item }">
              {{ formatDate(item.payment_date || item.created_at) }}
            </template>

            <template #item.contract="{ item }">
              <div class="d-flex flex-column gap-1">
                <span><strong>Договор:</strong> {{ item.contract_id || '-' }}</span>
                <span class="text-medium-emphasis text-sm"><strong>Контрагент/Сотр.:</strong> {{ formatCounterparty(item) }}</span>
              </div>
            </template>

            <template #item.fund_item="{ item }">
              <div class="d-flex flex-column gap-1">
                <span><strong>Фонд:</strong> {{ formatFund(item) }}</span>
                <span class="text-medium-emphasis text-sm"><strong>Статья:</strong> {{ formatItem(item) }}</span>
              </div>
            </template>

            <template #item.sum="{ item }">
              <span class="text-error font-weight-medium">
                -{{ formatCurrency(item.sum) }}
              </span>
            </template>

            <template #item.description="{ item }">
              <div class="text-truncate" style="max-inline-size: 24rem;">
                {{ item.description || '-' }}
              </div>
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
