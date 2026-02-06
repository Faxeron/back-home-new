<script setup lang="ts">
import ContractTemplatesNewPage from '@/modules/production/pages/contracts/templates/new.vue'

definePage({
  meta: {
    action: 'create',
    subject: 'contract_templates',
  },
})
</script>

<template>
  <ContractTemplatesNewPage />
</template>
