<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import EstimateTemplateSeptikEditor from '@/modules/estimates/components/EstimateTemplateSeptikEditor.vue'

const route = useRoute()
const templateId = computed(() => Number(route.params.id))
</script>

<template>
  <EstimateTemplateSeptikEditor :template-id="templateId" />
</template>
