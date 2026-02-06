<script setup lang="ts">
import { computed } from 'vue'
import { formatDateShort, formatSum } from '@/utils/formatters/finance'
import type { Transaction } from '@/types/finance'

const props = defineProps<{
  rows: Transaction[]
  loading?: boolean
  error?: string
  total?: number | null
  filter?: 'all' | 'income' | 'expense'
}>()

const emit = defineEmits<{
  (e: 'refresh'): void
  (e: 'update:filter', value: 'all' | 'income' | 'expense'): void
}>()

const activeFilter = computed({
  get: () => props.filter ?? 'all',
  set: value => emit('update:filter', value),
})

const subtitle = computed(() => {
  const total = typeof props.total === 'number' ? props.total : null
  if (total === null) return 'Последние транзакции'
  return `Всего ${total} транзакций`
})

const resolveTxnUi = (row: Transaction) => {
  const sign = Number(row.transaction_type?.sign ?? 0)

  if (sign < 0) {
    return { icon: 'tabler-arrow-up-right', color: 'error', profit: false }
  }

  if (sign > 0) {
    return { icon: 'tabler-arrow-down-right', color: 'success', profit: true }
  }

  return { icon: 'tabler-arrows-exchange', color: 'secondary', profit: true }
}

const signedSum = (row: Transaction) => {
  const sign = Number(row.transaction_type?.sign ?? 0)
  const prefix = sign < 0 ? '-' : sign > 0 ? '+' : ''
  const currency = row.sum?.currency ?? 'RUB'
  return `${prefix}${formatSum(row.sum)} ${currency}`
}
</script>

<template>
  <VCard class="h-100">
    <VCardItem>
      <div>
        <VCardTitle>Последние транзакции</VCardTitle>
        <VCardSubtitle>{{ subtitle }}</VCardSubtitle>
      </div>

      <template #append>
        <div class="d-flex align-center gap-2">
          <VBtn
            size="small"
            :variant="activeFilter === 'income' ? 'flat' : 'tonal'"
            color="success"
            @click="activeFilter = activeFilter === 'income' ? 'all' : 'income'"
          >
            Приходы
          </VBtn>
          <VBtn
            size="small"
            :variant="activeFilter === 'expense' ? 'flat' : 'tonal'"
            color="error"
            @click="activeFilter = activeFilter === 'expense' ? 'all' : 'expense'"
          >
            Расходы
          </VBtn>
          <VBtn
            size="small"
            variant="tonal"
            :to="{ path: '/finance/transactions' }"
          >
            Все
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

      <VList class="card-list">
        <VListItem
          v-for="row in props.rows"
          :key="row.id"
        >
          <template #prepend>
            <VAvatar
              size="34"
              :color="resolveTxnUi(row).color"
              variant="tonal"
              class="me-1"
              rounded
            >
              <VIcon
                :icon="resolveTxnUi(row).icon"
                size="20"
              />
            </VAvatar>
          </template>

          <VListItemTitle class="text-high-emphasis">
            {{ row.transaction_type?.name ?? `Транзакция #${row.id}` }}
          </VListItemTitle>
          <VListItemSubtitle>
            {{ row.cashbox?.name ?? 'Касса не указана' }} · {{ formatDateShort(row.created_at) }}
          </VListItemSubtitle>

          <template #append>
            <div class="d-flex flex-column align-end">
              <div :class="`${resolveTxnUi(row).profit ? 'text-success' : 'text-error'} font-weight-medium`">
                {{ signedSum(row) }}
              </div>
              <div class="d-flex align-center gap-2 mt-1">
                <VChip
                  size="x-small"
                  label
                  variant="tonal"
                  :color="row.is_paid ? 'success' : 'secondary'"
                >
                  {{ row.is_paid ? 'Оплачено' : 'Не оплачено' }}
                </VChip>
                <VChip
                  size="x-small"
                  label
                  variant="tonal"
                  :color="row.is_completed ? 'success' : 'secondary'"
                >
                  {{ row.is_completed ? 'Закрыта' : 'Открыта' }}
                </VChip>
              </div>
            </div>
          </template>
        </VListItem>
      </VList>

      <div
        v-if="!props.loading && !props.error && (!props.rows || props.rows.length === 0)"
        class="text-medium-emphasis text-center py-6"
      >
        Нет данных.
      </div>
    </VCardText>
  </VCard>
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 16px;
}
</style>
