<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import EstimatePublicView from '@/modules/estimates/components/EstimatePublicView.vue'

const route = useRoute()
const randomId = computed(() => String(route.params.randomId ?? ''))
</script>

<template>
  <EstimatePublicView :random-id="randomId" />
</template>
