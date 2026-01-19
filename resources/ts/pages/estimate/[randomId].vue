<script setup lang="ts">
import EstimatePublicPage from '@/modules/estimates/pages/estimate/[randomId].vue'
</script>

<route lang="yaml">
meta:
  layout: blank
  public: true
</route>

<template>
  <EstimatePublicPage />
</template>
