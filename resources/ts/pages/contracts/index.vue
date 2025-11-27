<script setup lang="ts">
import { contractsListEndpoint } from '@/api/contracts'
import type { Contract } from '@/types/finance'

const page = ref(1)
const itemsPerPage = ref(25)

const headers = [
  { title: 'ID', key: 'id', width: 70 },
  { title: 'Название', key: 'title' },
  { title: 'Компания', key: 'company_id' },
  { title: 'Контрагент', key: 'counterparty_id' },
  { title: 'Сумма', key: 'total_amount' },
  { title: 'Оплачено', key: 'paid_amount' },
  { title: 'Статус', key: 'system_status_code' },
]

const endpoint = contractsListEndpoint({ page, per_page: itemsPerPage })

const { data: response, execute: fetchContracts, isFetching } = await useApi<{ data: Contract[]; meta: any }>(endpoint)

const contracts = computed(() => response.value?.data ?? [])
const pagination = computed(() => response.value?.meta ?? { total: 0, per_page: itemsPerPage.value })

watch([page, itemsPerPage], () => fetchContracts())

const formatMoney = (value?: number) => new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 2 }).format(Number(value ?? 0))
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardTitle class="d-flex justify-space-between">
          <div>Договоры</div>
        </VCardTitle>
        <VDivider />
        <VCardText>
          <VDataTableServer
            v-model:page="page"
            v-model:items-per-page="itemsPerPage"
            :headers="headers"
            :items="contracts"
            :items-length="pagination.total ?? 0"
            :loading="isFetching"
            :items-per-page-options="[10, 25, 50, 100]"
            item-value="id"
            class="text-no-wrap"
            fixed-header
            height="650"
          >
            <template #item.total_amount="{ item }">
              {{ formatMoney(item.total_amount) }}
            </template>
            <template #item.paid_amount="{ item }">
              {{ formatMoney(item.paid_amount) }}
            </template>
            <template #item.system_status_code="{ item }">
              <VChip size="small" color="primary" label>
                {{ item.system_status_code || '-' }}
              </VChip>
            </template>

            <template #bottom>
              <div class="d-flex flex-wrap align-center justify-space-between gap-4 px-4 py-3">
                <div class="d-flex align-center gap-2">
                  <span class="text-sm text-medium-emphasis">Записей на странице:</span>
                  <AppSelect
                    :model-value="itemsPerPage"
                    :items="[10,25,50,100]"
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
