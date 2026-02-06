<script setup lang="ts">
import SeptikTemplateEditPage from '@/modules/estimates/pages/estimate-templates/septiks/[id].vue'

definePage({
  meta: {
    action: 'edit',
    subject: 'estimate_templates',
  },
})
</script>

<template>
  <SeptikTemplateEditPage />
</template>
