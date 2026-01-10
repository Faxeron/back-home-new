<script setup lang="ts">
import { onMounted } from 'vue'

const router = useRouter()

onMounted(() => {
  router.replace({ path: '/operations/contracts' })
})
</script>

<template>
  <div class="p-4 text-sm text-muted">Redirecting...</div>
</template>
