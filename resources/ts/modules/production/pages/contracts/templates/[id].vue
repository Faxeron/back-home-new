<script setup lang="ts">
import { useRoute } from 'vue-router'
import ContractTemplateEditor from '@/modules/production/components/contracts/ContractTemplateEditor.vue'

const route = useRoute()
const templateId = Number(route.params.id)

</script>

<template>
  <ContractTemplateEditor :template-id="templateId" />
</template>
