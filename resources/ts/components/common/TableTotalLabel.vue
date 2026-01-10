<script setup lang="ts">
import { computed } from 'vue'
import Button from 'primevue/button'

const props = defineProps<{
  label: string
  value: string | number
}>()

const text = computed(() => `${props.label}: ${props.value}`)
</script>

<template>
  <Button
    :label="text"
    size="small"
    text
    class="pointer-events-none text-muted"
  />
</template>
