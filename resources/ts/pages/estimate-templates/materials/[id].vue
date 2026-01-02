<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import EstimateTemplateMaterialEditor from '@/components/estimates/EstimateTemplateMaterialEditor.vue'

const route = useRoute()
const templateId = computed(() => Number(route.params.id))
</script>

<template>
  <EstimateTemplateMaterialEditor :template-id="templateId" />
</template>
