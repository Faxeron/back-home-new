<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useCookie } from '@/@core/composable/useCookie'
import { $api } from '@/utils/api'

type CashboxLogo = {
  id: number
  name: string
  file_path?: string | null
  logo_url?: string | null
  is_active?: boolean
}

definePage({
  meta: {
    action: 'manage',
    subject: 'all',
  },
})

const userData = useCookie<any>('userData')
const isSuperAdmin = computed(() => userData.value?.role === 'superadmin')

const loading = ref(false)
const saving = ref(false)
const deletingId = ref<number | null>(null)
const errorMessage = ref('')

const logos = ref<CashboxLogo[]>([])

const dialogOpen = ref(false)
const form = reactive({
  id: null as number | null,
  name: '',
  is_active: true,
})
const logoFile = ref<File | null>(null)
const logoPreview = ref<string | null>(null)
const removeLogo = ref(false)

const resetForm = () => {
  form.id = null
  form.name = ''
  form.is_active = true
  logoFile.value = null
  logoPreview.value = null
  removeLogo.value = false
  errorMessage.value = ''
}

const openCreate = () => {
  resetForm()
  dialogOpen.value = true
}

const openEdit = (row: CashboxLogo) => {
  resetForm()
  form.id = row.id
  form.name = row.name ?? ''
  form.is_active = row.is_active ?? true
  logoPreview.value = row.logo_url ?? null
  dialogOpen.value = true
}

const normalizeLogoFile = (value: File | File[] | null) => {
  if (Array.isArray(value)) return value[0] ?? null
  return value ?? null
}

const handleLogoChange = (value: File | File[] | null) => {
  logoFile.value = normalizeLogoFile(value)
  if (logoFile.value) {
    logoPreview.value = URL.createObjectURL(logoFile.value)
    removeLogo.value = false
  }
}

const loadLogos = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const response = await $api('settings/cashbox-logos', { query: { all: 1 } })
    logos.value = response?.data ?? []
  } catch (error: any) {
    logos.value = []
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось загрузить логотипы.'
  } finally {
    loading.value = false
  }
}

const submit = async () => {
  if (!form.name.trim()) {
    errorMessage.value = 'Укажите название логотипа.'
    return
  }

  saving.value = true
  errorMessage.value = ''
  try {
    const body = new FormData()
    body.append('name', form.name.trim())
    body.append('is_active', form.is_active ? '1' : '0')
    if (logoFile.value) body.append('logo', logoFile.value)
    if (removeLogo.value) body.append('logo_remove', '1')

    if (form.id) {
      body.append('_method', 'PATCH')
      await $api(`settings/cashbox-logos/${form.id}`, { method: 'POST', body })
    } else {
      await $api('settings/cashbox-logos', { method: 'POST', body })
    }

    dialogOpen.value = false
    await loadLogos()
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось сохранить логотип.'
  } finally {
    saving.value = false
  }
}

const removeLogoPreset = async (row: CashboxLogo) => {
  if (!window.confirm('Удалить логотип?')) return
  deletingId.value = row.id
  try {
    await $api(`settings/cashbox-logos/${row.id}`, { method: 'DELETE' })
    await loadLogos()
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось удалить логотип.'
  } finally {
    deletingId.value = null
  }
}

onMounted(() => {
  if (isSuperAdmin.value) {
    loadLogos()
  }
})
</script>

<template>
  <div class="d-flex flex-column gap-4">
    <div class="d-flex align-center justify-space-between flex-wrap gap-3">
      <h2 class="text-h5">Логотипы</h2>
      <VBtn color="primary" prepend-icon="tabler-plus" @click="openCreate">
        Добавить логотип
      </VBtn>
    </div>

    <VAlert
      v-if="!isSuperAdmin"
      type="warning"
      variant="tonal"
    >
      Доступ только для супер‑администратора.
    </VAlert>

    <VAlert
      v-else-if="errorMessage"
      type="error"
      variant="tonal"
    >
      {{ errorMessage }}
    </VAlert>

    <VProgressLinear v-if="loading" indeterminate color="primary" />

    <VRow v-if="isSuperAdmin && !loading">
      <VCol
        v-for="logo in logos"
        :key="logo.id"
        cols="12"
        sm="6"
        md="4"
        lg="3"
      >
        <VCard class="logo-card">
          <VCardText class="d-flex flex-column align-center gap-3">
            <div class="logo-preview">
              <img v-if="logo.logo_url" :src="logo.logo_url" :alt="logo.name">
              <div v-else class="logo-fallback">{{ logo.name?.[0] ?? '?' }}</div>
            </div>
            <div class="text-subtitle-1 text-center">{{ logo.name }}</div>
            <VChip v-if="logo.is_active" color="success" size="small">Активен</VChip>
          </VCardText>
          <VCardActions class="justify-space-between">
            <VBtn variant="text" prepend-icon="tabler-edit" @click="openEdit(logo)">Изменить</VBtn>
            <VBtn
              variant="text"
              color="error"
              prepend-icon="tabler-trash"
              :loading="deletingId === logo.id"
              @click="removeLogoPreset(logo)"
            >
              Удалить
            </VBtn>
          </VCardActions>
        </VCard>
      </VCol>
    </VRow>
  </div>

  <VDialog v-model="dialogOpen" max-width="520">
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <span>{{ form.id ? 'Изменить логотип' : 'Новый логотип' }}</span>
        <VBtn icon="tabler-x" variant="text" @click="dialogOpen = false" />
      </VCardTitle>
      <VCardText class="d-flex flex-column gap-4">
        <VTextField v-model="form.name" label="Название" hide-details />
        <VSwitch v-model="form.is_active" label="Активен" inset />

        <div class="d-flex flex-column gap-2">
          <div class="text-sm font-medium">Файл PNG</div>
          <div class="d-flex align-center gap-3">
            <div class="logo-preview sm">
              <img v-if="logoPreview" :src="logoPreview" alt="preview">
              <div v-else class="logo-fallback">{{ form.name?.[0] ?? '?' }}</div>
            </div>
            <VFileInput
              label="Загрузить PNG"
              accept="image/png"
              prepend-icon="tabler-upload"
              :model-value="logoFile"
              @update:modelValue="handleLogoChange"
            />
          </div>
          <VSwitch v-model="removeLogo" label="Удалить текущий файл" inset :disabled="!logoPreview" />
        </div>
      </VCardText>
      <VCardActions class="justify-end gap-2">
        <VBtn variant="text" @click="dialogOpen = false">Отмена</VBtn>
        <VBtn color="primary" :loading="saving" @click="submit">Сохранить</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.logo-card {
  min-height: 220px;
}

.logo-preview {
  width: 120px;
  height: 72px;
  border-radius: 14px;
  background: linear-gradient(145deg, rgba(0, 0, 0, 0.08), rgba(255, 255, 255, 0.15));
  display: grid;
  place-items: center;
  overflow: hidden;
}

.logo-preview img {
  max-width: 84px;
  max-height: 44px;
  object-fit: contain;
}

.logo-preview.sm {
  width: 84px;
  height: 52px;
}

.logo-fallback {
  font-weight: 700;
  color: rgba(var(--v-theme-on-surface), 0.7);
}
</style>
