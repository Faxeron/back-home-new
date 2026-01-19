<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import EstimateEditor from '@/modules/estimates/components/EstimateEditor.vue'

const route = useRoute()
const estimateId = computed(() => Number(route.params.id))
</script>

<template>
  <EstimateEditor :estimate-id="estimateId" />
</template>
