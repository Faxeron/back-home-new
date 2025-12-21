<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  status: string | null
  map?: Record<string, { text: string; bg: string; color: string }>
}>()

const statusObj = computed(() => {
  if (!props.status) return null

  const key = String(props.status).toUpperCase()

  return props.map?.[key] || {
    text: props.status,
    bg: '#f1f2f3',
    color: '#6b7280',
  }
})
</script>

<template>
  <span
    v-if="statusObj"
    class="badge"
    :style="{ backgroundColor: statusObj.bg, color: statusObj.color }"
  >
    {{ statusObj.text }}
  </span>
</template>

<style scoped>
.badge {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  font-size: 12px;
  font-weight: 600;
  border-radius: 6px;
  white-space: nowrap;
}
</style>
