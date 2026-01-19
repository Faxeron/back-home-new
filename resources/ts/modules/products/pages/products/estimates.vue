<script setup lang="ts">
import { PRODUCT_PLACEHOLDER_LABELS } from '@/modules/products/config/productPlaceholders.config'
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardTitle>{{ PRODUCT_PLACEHOLDER_LABELS.estimatesTitle }}</VCardTitle>
        <VCardText>{{ PRODUCT_PLACEHOLDER_LABELS.description }}</VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>
