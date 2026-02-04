<script setup lang="ts">
import { computed } from 'vue'

type CashboxLike = {
  name?: string | null
  logo_url?: string | null
}

const props = defineProps<{
  cashbox?: CashboxLike | null
  name?: string | null
  logoUrl?: string | null
  showName?: boolean
  size?: 'sm' | 'md'
}>()

const resolvedName = computed(() => props.cashbox?.name ?? props.name ?? '\u2014')
const resolvedLogo = computed(() => props.cashbox?.logo_url ?? props.logoUrl ?? null)
const showName = computed(() => props.showName !== false)
const sizeClass = computed(() => (props.size === 'sm' ? 'cashbox-card--sm' : 'cashbox-card--md'))
const initial = computed(() => {
  const value = resolvedName.value?.trim()
  return value ? value[0].toUpperCase() : ''
})
</script>

<template>
  <span class="cashbox-badge">
    <span class="cashbox-card" :class="sizeClass" aria-hidden="true">
      <img
        v-if="resolvedLogo"
        :src="resolvedLogo"
        :alt="resolvedName"
        class="cashbox-card__logo"
      />
      <span v-else class="cashbox-card__fallback">{{ initial }}</span>
    </span>
    <span v-if="showName" class="cashbox-badge__name">{{ resolvedName }}</span>
  </span>
</template>

<style scoped>
.cashbox-badge {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
  max-width: 100%;
}

.cashbox-badge__name {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  min-width: 0;
  max-width: 100%;
  flex: 1 1 auto;
  color: inherit;
  font-weight: 500;
}

.cashbox-card {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background: #ffffff;
  border: 1px solid rgba(0, 0, 0, 0.08);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
  flex-shrink: 0;
}

.cashbox-card--md {
  width: 52px;
  height: 34px;
}

.cashbox-card--sm {
  width: 42px;
  height: 28px;
}

.cashbox-card__logo {
  max-width: 90%;
  max-height: 90%;
  object-fit: contain;
}

.cashbox-card__fallback {
  font-weight: 700;
  font-size: 12px;
  color: rgba(var(--v-theme-on-surface), 0.6);
}
</style>
