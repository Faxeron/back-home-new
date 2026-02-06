<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useAbility } from '@casl/vue'
import { $api } from '@/utils/api'

type MarginSettings = {
  red_max: number
  orange_max: number
}

const loading = ref(false)
const saving = ref(false)
const errorMessage = ref('')
const snackbarOpen = ref(false)
const snackbarText = ref('')
const snackbarColor = ref<'success' | 'error'>('success')
const ability = useAbility()
const canEdit = computed(() => ability.can('edit', 'settings.margin'))

const form = reactive<MarginSettings>({
  red_max: 10,
  orange_max: 20,
})

const showSnackbar = (text: string, color: 'success' | 'error' = 'success') => {
  snackbarText.value = text
  snackbarColor.value = color
  snackbarOpen.value = true
}

const loadSettings = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const response: any = await $api('settings/margin')
    const data = response?.data as MarginSettings | undefined
    if (data) {
      form.red_max = Number(data.red_max ?? 0)
      form.orange_max = Number(data.orange_max ?? 0)
    }
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось загрузить настройки маржи.'
  } finally {
    loading.value = false
  }
}

const saveSettings = async () => {
  if (!canEdit.value) return
  saving.value = true
  errorMessage.value = ''
  try {
    await $api('settings/margin', {
      method: 'PUT',
      body: {
        red_max: form.red_max,
        orange_max: form.orange_max,
      },
    })
    showSnackbar('Настройки маржи сохранены.', 'success')
  } catch (error: any) {
    const message = error?.response?.data?.message ?? 'Не удалось сохранить настройки маржи.'
    errorMessage.value = message
    showSnackbar(message, 'error')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await loadSettings()
})
</script>

<template>
  <VCard>
    <VCardTitle>Настройки цвета маржи</VCardTitle>
    <VCardText>
      <div v-if="errorMessage" class="text-sm" style="color: #b91c1c;">
        {{ errorMessage }}
      </div>
      <div v-if="loading" class="text-sm text-muted">Загрузка...</div>
      <VRow v-else>
        <VCol cols="12" md="6">
          <VTextField
            v-model.number="form.red_max"
            label="Красный до, %"
            type="number"
            min="0"
            max="100"
            :disabled="!canEdit"
          />
        </VCol>
        <VCol cols="12" md="6">
          <VTextField
            v-model.number="form.orange_max"
            label="Оранжевый до, %"
            type="number"
            min="0"
            max="100"
            :disabled="!canEdit"
          />
        </VCol>
      </VRow>
      <div class="d-flex justify-end mt-4">
        <VBtn color="primary" :loading="saving" :disabled="!canEdit" @click="saveSettings">Сохранить</VBtn>
      </div>
    </VCardText>
  </VCard>

  <VSnackbar v-model="snackbarOpen" :color="snackbarColor" location="bottom end">
    {{ snackbarText }}
  </VSnackbar>
</template>
