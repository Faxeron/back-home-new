<script setup lang="ts">
import { useTheme } from 'vuetify'
import { storeToRefs } from 'pinia'
import ScrollToTop from '@core/components/ScrollToTop.vue'
import initCore from '@core/initCore'
import { initConfigStore, useConfigStore } from '@core/stores/config'
import { hexToRgb } from '@core/utils/colorConverter'
import { useAppSnackbarStore } from '@/stores/appSnackbar'

const { global } = useTheme()

// ℹ️ Sync current theme with initial loader theme
initCore()
initConfigStore()

const configStore = useConfigStore()
const appSnackbar = useAppSnackbarStore()
const { open, text, color, timeout, location } = storeToRefs(appSnackbar)
</script>

<template>
  <VLocaleProvider :rtl="configStore.isAppRTL">
    <!-- ℹ️ This is required to set the background color of active nav link based on currently active global theme's primary -->
    <VApp :style="`--v-global-theme-primary: ${hexToRgb(global.current.value.colors.primary)}`">
      <RouterView />
      <VSnackbar v-model="open" :color="color" :timeout="timeout" :location="location">
        {{ text }}
      </VSnackbar>
      <ScrollToTop />
    </VApp>
  </VLocaleProvider>
</template>
