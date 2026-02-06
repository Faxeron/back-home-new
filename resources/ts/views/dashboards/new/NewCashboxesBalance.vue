<script setup lang="ts">
import { computed, shallowRef, watch } from 'vue'
import { useTheme } from 'vuetify'
import { useTransition, TransitionPresets } from '@vueuse/core'
import { formatSum } from '@/utils/formatters/finance'
import CashboxCell from '@/components/cashboxes/CashboxCell.vue'

export type CashboxBalanceRow = {
  id: number
  name?: string | null
  balance?: number | null
  logo_url?: string | null
}

const props = defineProps<{
  rows: CashboxBalanceRow[]
  total: number
  loading?: boolean
  error?: string
}>()

const emit = defineEmits<{
  (e: 'refresh'): void
}>()

const theme = useTheme()

type UiRow = CashboxBalanceRow & {
  balanceValue: number
  delta: number | null
}

const deltaById = shallowRef<Map<number, number | null>>(new Map())
const prevById = shallowRef<Map<number, number>>(new Map())

watch(
  () => props.rows,
  rows => {
    const nextPrev = new Map<number, number>()
    const nextDelta = new Map<number, number | null>()

    for (const row of rows ?? []) {
      const balance = Number(row.balance ?? 0) || 0
      const prev = prevById.value.get(row.id)
      nextDelta.set(row.id, typeof prev === 'number' ? balance - prev : null)
      nextPrev.set(row.id, balance)
    }

    deltaById.value = nextDelta
    prevById.value = nextPrev
  },
  { immediate: true },
)

const sorted = computed<UiRow[]>(() =>
  [...(props.rows ?? [])]
    .map(row => ({
      ...row,
      balanceValue: Number(row.balance ?? 0) || 0,
      delta: deltaById.value.get(row.id) ?? null,
    }))
    .sort((a, b) => b.balanceValue - a.balanceValue),
)

const animatedTotal = useTransition(
  computed(() => Number(props.total ?? 0) || 0),
  { duration: 600, transition: TransitionPresets.easeOutCubic },
)

const palette = computed(() => {
  const c = theme.current.value.colors
  return [c.primary, c.success, c.info, c.warning, c.error, c.secondary]
})

const colorForIndex = (i: number) => palette.value[i % palette.value.length]

const percentOfTotal = (value: number) => {
  const total = Number(props.total ?? 0) || 0
  if (total <= 0) return 0
  return Math.max(0, Math.min(100, (value / total) * 100))
}

const segments = computed(() => {
  const top = sorted.value.slice(0, 6)
  const rest = sorted.value.slice(6)
  const otherSum = rest.reduce((s, r) => s + r.balanceValue, 0)

  const segs = top.map((r, idx) => ({
    key: `c${r.id}`,
    label: r.name ?? `#${r.id}`,
    value: r.balanceValue,
    percent: percentOfTotal(r.balanceValue),
    color: colorForIndex(idx),
  }))

  if (otherSum > 0) {
    segs.push({
      key: 'other',
      label: 'Остальные',
      value: otherSum,
      percent: percentOfTotal(otherSum),
      color: theme.current.value.colors['on-surface'],
    })
  }

  return segs
})

const deltaUi = (delta: number | null) => {
  if (delta === null) return { color: 'secondary', text: '—' }
  if (delta > 0) return { color: 'success', text: `+${formatSum(delta)} RUB` }
  if (delta < 0) return { color: 'error', text: `${formatSum(delta)} RUB` }
  return { color: 'secondary', text: '0 RUB' }
}
</script>

<template>
  <VCard class="h-100">
    <VCardItem>
      <VCardTitle>Баланс касс</VCardTitle>
      <template #append>
        <div class="d-flex align-center gap-2">
          <VBtn
            size="small"
            variant="tonal"
            :to="{ path: '/finance/balance' }"
          >
            Открыть
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

      <div class="d-flex align-center justify-space-between flex-wrap gap-3 mb-4">
        <div>
          <div class="text-sm text-medium-emphasis">
            Итого по кассам
          </div>
          <div class="text-h4 font-weight-semibold">
            {{ formatSum(animatedTotal) }} RUB
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VChip size="small" variant="tonal" color="secondary">
            Касс: {{ sorted.length }}
          </VChip>
        </div>
      </div>

      <div class="distribution mb-6" aria-hidden="true">
        <div class="distribution__track">
          <div
            v-for="seg in segments"
            :key="seg.key"
            class="distribution__seg"
            :style="{
              width: `${seg.percent}%`,
              background: `linear-gradient(90deg, ${seg.color} 0%, rgba(255,255,255,0.08) 100%)`,
            }"
          />
        </div>
        <div class="distribution__legend text-sm text-medium-emphasis mt-2">
          <span v-for="seg in segments" :key="`${seg.key}-l`" class="distribution__legend-item">
            <span class="distribution__dot" :style="{ backgroundColor: seg.color }" />
            {{ seg.label }}
          </span>
        </div>
      </div>

      <div v-if="sorted.length === 0 && !props.loading && !props.error" class="text-medium-emphasis text-center py-6">
        Нет данных.
      </div>

      <VList v-else class="card-list">
        <VListItem
          v-for="(row, idx) in sorted"
          :key="row.id"
        >
          <div class="w-100">
            <div class="d-flex align-center justify-space-between gap-3">
              <div class="min-w-0">
                <CashboxCell :cashbox="row" size="sm" />
              </div>

              <div class="d-flex align-center gap-2 flex-shrink-0">
                <VChip
                  size="x-small"
                  label
                  variant="tonal"
                  :color="deltaUi(row.delta).color"
                >
                  {{ deltaUi(row.delta).text }}
                </VChip>
                <span class="text-high-emphasis font-weight-semibold">
                  {{ formatSum(row.balanceValue) }} RUB
                </span>
              </div>
            </div>

            <div class="bar mt-2">
              <div class="bar__track">
                <div
                  class="bar__fill"
                  :style="{
                    width: `${percentOfTotal(row.balanceValue)}%`,
                    background: `linear-gradient(90deg, ${colorForIndex(idx)} 0%, rgba(255,255,255,0.10) 100%)`,
                    boxShadow: `0 0 18px rgba(0,0,0,0.12)`,
                  }"
                />
              </div>
            </div>
          </div>
        </VListItem>
      </VList>
    </VCardText>
  </VCard>
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 14px;
}

.distribution__track {
  height: 10px;
  border-radius: 999px;
  overflow: hidden;
  display: flex;
  background: rgba(var(--v-theme-on-surface), 0.08);
}

.distribution__seg {
  height: 100%;
  transition: width 600ms cubic-bezier(0.2, 0.9, 0.2, 1);
}

.distribution__legend {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.distribution__legend-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-width: 0;
}

.distribution__dot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  flex-shrink: 0;
}

.bar__track {
  height: 8px;
  border-radius: 999px;
  overflow: hidden;
  background: rgba(var(--v-theme-on-surface), 0.10);
}

.bar__fill {
  height: 100%;
  border-radius: 999px;
  transition: width 650ms cubic-bezier(0.2, 0.9, 0.2, 1);
}
</style>
