<script setup lang="ts">
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import logoLight from '@images/logos/logo_septik_sever.png'
import logoDark from '@images/logos/logo_septik_sever_dark.png'

const logoSrc = useGenerateImageVariant(logoLight, logoDark)
</script>

<template>
  <img
    :src="logoSrc"
    alt="Septik Sever"
    class="app-logo-img"
  >
</template>

<style scoped>
.app-logo-img {
  display: block;
  height: 28px;
  width: auto;
}
</style>
