<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import EstimatePublicView from '@/components/estimates/EstimatePublicView.vue'

definePage({
  meta: {
    layout: 'blank',
    public: true,
  },
})

const route = useRoute()
const randomId = computed(() => String(route.params.randomId ?? ''))
</script>

<template>
  <EstimatePublicView :random-id="randomId" />
</template>
