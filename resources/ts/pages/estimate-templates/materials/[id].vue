<script setup lang="ts">
import MaterialTemplateEditPage from '@/modules/estimates/pages/estimate-templates/materials/[id].vue'

definePage({
  meta: {
    action: 'edit',
    subject: 'estimate_templates',
  },
})
</script>

<template>
  <MaterialTemplateEditPage />
</template>
