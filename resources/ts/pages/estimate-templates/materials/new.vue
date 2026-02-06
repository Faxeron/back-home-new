<script setup lang="ts">
import MaterialTemplateCreatePage from '@/modules/estimates/pages/estimate-templates/materials/new.vue'

definePage({
  meta: {
    action: 'create',
    subject: 'estimate_templates',
  },
})
</script>

<template>
  <MaterialTemplateCreatePage />
</template>
