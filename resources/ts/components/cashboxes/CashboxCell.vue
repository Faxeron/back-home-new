<script setup lang="ts">
import { computed } from 'vue'
import CashboxBadge from '@/components/cashboxes/CashboxBadge.vue'

type CashboxLike = {
  name?: string | null
  logo_url?: string | null
}

const props = defineProps<{
  cashbox?: CashboxLike | null
  size?: 'sm' | 'md'
}>()

const resolvedName = computed(() => props.cashbox?.name ?? '\u2014')
</script>

<template>
  <div class="cashbox-cell">
    <CashboxBadge :cashbox="cashbox" :show-name="false" :size="size" />
    <span class="cashbox-cell__name">{{ resolvedName }}</span>
  </div>
</template>

<style scoped>
.cashbox-cell {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.cashbox-cell__name {
  min-width: 0;
  max-width: 100%;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: inherit;
  font-weight: 500;
}
</style>
