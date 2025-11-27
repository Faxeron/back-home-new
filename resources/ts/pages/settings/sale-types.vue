<script setup lang="ts">
import { saleTypesEndpoint } from '@/api/settings'
import type { SaleType } from '@/types/finance'

const page = ref(1)
const itemsPerPage = ref(25)

const headers = [
  { title: 'ID', key: 'id', width: 70 },
  { title: 'Название', key: 'name' },
]

const endpoint = saleTypesEndpoint({ page, per_page: itemsPerPage })
const { data: response, execute: fetchItems, isFetching } = await useApi<{ data: SaleType[]; meta: any }>(endpoint)

const rows = computed(() => response.value?.data ?? [])
const pagination = computed(() => response.value?.meta ?? { total: 0, per_page: itemsPerPage.value })

watch([page, itemsPerPage], () => fetchItems())
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardTitle>Типы продаж</VCardTitle>
        <VDivider />
        <VCardText>
          <VDataTableServer
            v-model:page="page"
            v-model:items-per-page="itemsPerPage"
            :headers="headers"
            :items="rows"
            :items-length="pagination.total ?? 0"
            :loading="isFetching"
            :items-per-page-options="[10, 25, 50, 100]"
            item-value="id"
            class="text-no-wrap"
          />
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>
