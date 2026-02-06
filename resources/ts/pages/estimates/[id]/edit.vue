<script setup lang="ts">
import EstimateEditPage from '@/modules/estimates/pages/estimates/[id]/edit.vue'

definePage({
  meta: {
    action: 'edit',
    subject: 'estimates',
  },
})
</script>

<template>
  <EstimateEditPage />
</template>
