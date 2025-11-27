<script setup lang="ts">
import { transactionsListEndpoint } from '@/api/finance/transactions'
import type { Transaction } from '@/types/finance'

const page = ref(1)
const itemsPerPage = ref(25)

const perPageOptions = [
  { title: '10', value: 10 },
  { title: '25', value: 25 },
  { title: '50', value: 50 },
  { title: '100', value: 100 },
]

const headers = [
  { title: '', key: 'data-table-expand', sortable: false, width: 48 },
  { title: 'ID', key: 'id', sortable: true, width: 60 },
  { title: 'Дата', key: 'created_at', sortable: true },
  { title: 'Тип', key: 'transaction_type_id', sortable: true },
  { title: 'Касса', key: 'cash_box_id' },
  { title: 'Компания', key: 'company_id' },
  { title: 'Сумма', key: 'sum', sortable: true },
  { title: 'Метод оплаты', key: 'payment_method_id' },
  { title: 'Контрагент', key: 'counterparty_id' },
  { title: 'Договор', key: 'contract_id' },
  { title: 'Статус', key: 'status' },
  { title: 'Заметки', key: 'notes' },
  { title: 'Действия', key: 'actions', sortable: false, width: 64 },
]

const filters = reactive({
  dateFrom: null as string | null,
  dateTo: null as string | null,
  transactionTypeId: null as number | string | null,
  companyId: null as number | null,
  cashBoxId: null as number | null,
  contractId: null as number | null,
  counterpartyId: null as number | null,
  paymentMethodId: null as number | string | null,
  search: '',
})

const include = 'cashBox,company,counterparty,contract,transactionType,paymentMethod'

const transactionsEndpoint = transactionsListEndpoint({
  page,
  per_page: itemsPerPage,
  date_from: computed(() => filters.dateFrom || undefined),
  date_to: computed(() => filters.dateTo || undefined),
  transaction_type_id: computed(() => filters.transactionTypeId || undefined),
  company_id: computed(() => filters.companyId || undefined),
  cash_box_id: computed(() => filters.cashBoxId || undefined),
  contract_id: computed(() => filters.contractId || undefined),
  counterparty_id: computed(() => filters.counterpartyId || undefined),
  payment_method_id: computed(() => filters.paymentMethodId || undefined),
  search: computed(() => filters.search || undefined),
  include,
})

const {
  data: transactionsResponse,
  execute: fetchTransactions,
  isFetching,
} = await useApi<{ data: Transaction[]; meta: any }>(transactionsEndpoint)

const transactions = computed(() => transactionsResponse.value?.data ?? [])
const pagination = computed(() => transactionsResponse.value?.meta ?? { total: 0, per_page: itemsPerPage.value })

watch(itemsPerPage, () => {
  page.value = 1
})

watch([page, itemsPerPage], () => {
  fetchTransactions()
})

watch(filters, () => {
  page.value = 1
  fetchTransactions()
}, { deep: true })

const statusChipProps = (value: boolean | undefined) => value
  ? { color: 'success', text: 'Да' }
  : { color: 'secondary', text: 'Нет' }

const formatCurrency = (money: Transaction['sum']) => {
  if (!money)
    return '-'
  const number = Number(money.amount ?? 0)
  return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: money.currency || 'RUB', maximumFractionDigits: 2 }).format(number)
}

const formatDate = (value?: string) => value ? new Date(value).toLocaleDateString('ru-RU') : '-'
const formatDateTime = (value?: string) => value ? new Date(value).toLocaleString('ru-RU') : '-'
const formatCompany = (item: Transaction) => item?.company?.name || item?.company_id || '-'
const formatCashBox = (item: Transaction) => item?.cash_box?.name || item?.cash_box_id || '-'
const formatCounterparty = (item: Transaction) => item?.counterparty?.name || item?.counterparty_id || '-'
const formatType = (item: Transaction) => item?.transaction_type?.name || item?.transaction_type_id || '-'
const formatPaymentMethod = (item: Transaction) => item?.payment_method?.name || item?.payment_method_id || '-'

const resolveTypeChip = (item: Transaction) => {
  const sign = item?.transaction_type?.sign ?? 0
  const color = sign > 0 ? '#28C76F' : sign < 0 ? '#EA5455' : '#00CFE8'
  return { color, text: formatType(item) }
}

const resolveSumColor = (item: Transaction) => {
  const sign = item?.transaction_type?.sign ?? 0
  if (sign > 0) return 'success'
  if (sign < 0) return 'error'
  return 'info'
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardTitle class="d-flex flex-column align-start py-4">
          <div class="text-h5">
            Транзакции
          </div>
          <VCardSubtitle class="text-wrap">
            Список транзакций
          </VCardSubtitle>
        </VCardTitle>

        <VDivider />

        <VCardText>
          <div class="d-flex flex-wrap gap-4 mb-4 align-center">
            <AppTextField
              v-model="filters.search"
              prepend-inner-icon="tabler-search"
              placeholder="Поиск по заметкам"
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
              v-model="filters.transactionTypeId"
              label="Тип (ID/код)"
              density="compact"
              style="inline-size: 10rem;"
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
              v-model.number="filters.cashBoxId"
              label="Касса (ID)"
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
            <AppTextField
              v-model="filters.paymentMethodId"
              label="Метод оплаты (ID/код)"
              density="compact"
              style="inline-size: 10rem;"
              clearable
            />
          </div>

          <VDataTableServer
            v-model:page="page"
            v-model:items-per-page="itemsPerPage"
            :headers="headers"
            :items="transactions"
            :items-length="pagination.total ?? 0"
            :loading="isFetching"
            :items-per-page-options="[10, 25, 50, 100]"
            item-value="id"
            expand-on-click
            show-expand
            class="text-no-wrap"
            fixed-header
            height="650"
          >
            <template #item.id="{ item }">
              <span class="text-medium-emphasis text-caption" style="min-inline-size: 60px; display: inline-block;">{{ item.id }}</span>
            </template>

            <template #item.created_at="{ item }">
              {{ formatDate(item.created_at) }}
            </template>

            <template #item.transaction_type_id="{ item }">
              <VChip
                size="small"
                :style="`background-color:${ resolveTypeChip(item).color }; color: white;`"
                label
              >
                {{ resolveTypeChip(item).text }}
              </VChip>
            </template>

            <template #item.cash_box_id="{ item }">
              {{ formatCashBox(item) }}
            </template>

            <template #item.company_id="{ item }">
              {{ formatCompany(item) }}
            </template>

            <template #item.sum="{ item }">
              <span :class="`text-${ resolveSumColor(item) } font-weight-medium`">
                {{ formatCurrency(item.sum) }}
              </span>
            </template>

            <template #item.payment_method_id="{ item }">
              {{ formatPaymentMethod(item) }}
            </template>

            <template #item.counterparty_id="{ item }">
              {{ formatCounterparty(item) }}
            </template>

            <template #item.status="{ item }">
              <div class="d-flex flex-wrap gap-2">
                <VChip
                  v-bind="statusChipProps(item.is_paid)"
                  size="small"
                  label
                >
                  Оплачено: {{ statusChipProps(item.is_paid).text }}
                </VChip>
                <VChip
                  v-bind="statusChipProps(item.is_completed)"
                  size="small"
                  label
                >
                  Закрыто: {{ statusChipProps(item.is_completed).text }}
                </VChip>
              </div>
            </template>

            <template #item.notes="{ item }">
              {{ item.notes || '-' }}
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

            <template #expanded-row="{ columns, item }">
              <tr class="v-data-table__tr">
                <td :colspan="columns.length">
                  <VRow class="text-body-2">
                    <VCol cols="12" md="3">
                      <strong>Создано:</strong>
                      <div>{{ formatDateTime(item.created_at) }}</div>
                      <div class="text-medium-emphasis">
                        ID автора: {{ item.created_by ?? '-' }}
                      </div>
                    </VCol>
                    <VCol cols="12" md="3">
                      <strong>Обновлено:</strong>
                      <div>{{ formatDateTime(item.updated_at) }}</div>
                      <div class="text-medium-emphasis">
                        ID редактора: {{ item.updated_by ?? '-' }}
                      </div>
                    </VCol>
                    <VCol cols="12" md="3">
                      <strong>Дата оплаты:</strong>
                      <div>{{ formatDateTime(item.date_is_paid) }}</div>
                    </VCol>
                    <VCol cols="12" md="3">
                      <strong>Дата закрытия:</strong>
                      <div>{{ formatDateTime(item.date_is_completed) }}</div>
                    </VCol>
                    <VCol cols="12">
                      <strong>Заметки:</strong>
                      <div>{{ item.notes || '-' }}</div>
                    </VCol>
                  </VRow>
                </td>
              </tr>
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
