<script setup lang="ts">
import { computed, ref, watch } from 'vue'

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
const logoSize = computed(() => (props.size === 'sm' ? 22 : 28))
const initial = computed(() => {
  const value = resolvedName.value?.trim()
  return value ? value[0].toUpperCase() : ''
})

const logoFailed = ref(false)
watch(resolvedLogo, () => {
  // If logo URL changed, allow a new attempt.
  logoFailed.value = false
})
</script>

<template>
  <span class="cashbox-badge">
    <span
      class="cashbox-logo"
      :style="{ width: `${logoSize}px`, height: `${logoSize}px` }"
      aria-hidden="true"
    >
      <img
        v-if="resolvedLogo && !logoFailed"
        :src="resolvedLogo"
        :alt="resolvedName"
        class="cashbox-logo__img"
        @error="logoFailed = true"
      />
      <VAvatar
        v-else
        :size="logoSize"
        color="secondary"
        rounded="sm"
        class="cashbox-logo__fallback"
      >
        <span class="cashbox-logo__initial">{{ initial }}</span>
      </VAvatar>
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

.cashbox-logo {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  overflow: hidden;
}

.cashbox-logo__img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  border-radius: 6px;
}

.cashbox-logo__fallback {
  width: 100%;
  height: 100%;
  font-weight: 700;
}

.cashbox-logo__initial {
  font-weight: 700;
  font-size: 12px;
}
</style>
