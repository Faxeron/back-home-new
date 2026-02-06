<script setup lang="ts">
import ContractTemplatesEditPage from '@/modules/production/pages/contracts/templates/[id].vue'

definePage({
  meta: {
    action: 'edit',
    subject: 'contract_templates',
  },
})
</script>

<template>
  <ContractTemplatesEditPage />
</template>
