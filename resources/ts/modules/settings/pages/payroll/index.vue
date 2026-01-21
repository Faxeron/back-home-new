<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { $api } from '@/utils/api'

type PayrollSettings = {
  manager_fixed: number
  manager_percent: number
  measurer_fixed: number
  measurer_percent: number
}

const loading = ref(false)
const saving = ref(false)
const errorMessage = ref('')
const snackbarOpen = ref(false)
const snackbarText = ref('')
const snackbarColor = ref<'success' | 'error'>('success')

const form = reactive<PayrollSettings>({
  manager_fixed: 1000,
  manager_percent: 7,
  measurer_fixed: 1000,
  measurer_percent: 5,
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
    const response: any = await $api('settings/payroll')
    const data = response?.data as PayrollSettings | undefined
    if (data) {
      form.manager_fixed = Number(data.manager_fixed ?? 0)
      form.manager_percent = Number(data.manager_percent ?? 0)
      form.measurer_fixed = Number(data.measurer_fixed ?? 0)
      form.measurer_percent = Number(data.measurer_percent ?? 0)
    }
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось загрузить настройки.'
  } finally {
    loading.value = false
  }
}

const saveSettings = async () => {
  saving.value = true
  errorMessage.value = ''
  try {
    await $api('settings/payroll', {
      method: 'PUT',
      body: {
        manager_fixed: form.manager_fixed,
        manager_percent: form.manager_percent,
        measurer_fixed: form.measurer_fixed,
        measurer_percent: form.measurer_percent,
      },
    })
    showSnackbar('Настройки сохранены.', 'success')
  } catch (error: any) {
    const message = error?.response?.data?.message ?? 'Не удалось сохранить настройки.'
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
    <VCardTitle>Настройки З/П</VCardTitle>
    <VCardText>
      <div v-if="errorMessage" class="text-sm" style="color: #b91c1c;">
        {{ errorMessage }}
      </div>
      <div v-if="loading" class="text-sm text-muted">Загрузка...</div>
      <VRow v-else>
        <VCol cols="12" md="6">
          <VTextField
            v-model.number="form.manager_fixed"
            label="Менеджер: фикс, ₽"
            type="number"
            min="0"
          />
        </VCol>
        <VCol cols="12" md="6">
          <VTextField
            v-model.number="form.manager_percent"
            label="Менеджер: % от маржи"
            type="number"
            min="0"
            max="100"
          />
        </VCol>
        <VCol cols="12" md="6">
          <VTextField
            v-model.number="form.measurer_fixed"
            label="Замерщик: фикс, ₽"
            type="number"
            min="0"
          />
        </VCol>
        <VCol cols="12" md="6">
          <VTextField
            v-model.number="form.measurer_percent"
            label="Замерщик: % от маржи"
            type="number"
            min="0"
            max="100"
          />
        </VCol>
      </VRow>
      <div class="d-flex justify-end mt-4">
        <VBtn color="primary" :loading="saving" @click="saveSettings">Сохранить</VBtn>
      </div>
    </VCardText>
  </VCard>

  <VSnackbar v-model="snackbarOpen" :color="snackbarColor" location="bottom end">
    {{ snackbarText }}
  </VSnackbar>
</template>
