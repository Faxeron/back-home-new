<script setup lang="ts">
import { watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useContractTemplateEditor } from '@/modules/production/composables/useContractTemplateEditor'

const props = defineProps<{ templateId?: number | null }>()

const route = useRoute()
const router = useRouter()

const {
  templateId,
  form,
  productTypes,
  templateFiles,
  filesLoading,
  uploading,
  loading,
  saving,
  errorMessage,
  loadTemplate,
  saveTemplate,
  uploadTemplateFile,
} = useContractTemplateEditor(props.templateId ?? null)

watch(
  () => route.params.id,
  async value => {
    if (!value) return
    templateId.value = Number(value)
    await loadTemplate()
  },
)

const handleSave = async () => {
  const id = await saveTemplate()
  if (id && route.path.includes('/new')) {
    await router.replace({ path: `/operations/contracts/templates/${id}` })
  }
}

const handleTemplateFileSelect = async (value: File | File[] | null) => {
  const file = Array.isArray(value) ? value[0] : value
  await uploadTemplateFile(file ?? null)
}
</script>

<template>
  <div class="flex flex-column gap-4">
    <div class="flex items-center justify-between gap-3">
      <h2 class="text-xl font-semibold">
        {{ templateId ? `Шаблон договора #${templateId}` : 'Создать шаблон договора' }}
      </h2>
      <VBtn
        color="primary"
        prepend-icon="tabler-device-floppy"
        :loading="saving"
        @click="handleSave"
      >
        Сохранить
      </VBtn>
    </div>

    <VCard>
      <VCardText>
        <div v-if="errorMessage" class="mb-3 text-sm text-error">
          {{ errorMessage }}
        </div>
        <VRow>
          <VCol cols="12" md="6">
            <VTextField v-model="form.name" label="Название" hide-details />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="form.short_name" label="Короткое название" hide-details />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect
              v-model="form.document_type"
              :items="[
                { title: 'Поставка', value: 'supply' },
                { title: 'Монтаж', value: 'install' },
                { title: 'Совмещенный', value: 'combined' },
              ]"
              item-title="title"
              item-value="value"
              label="Тип договора"
              hide-details
            />
          </VCol>
          <VCol cols="12" md="6">
            <VFileInput
              label="DOCX шаблон"
              accept=".docx"
              hide-details
              prepend-icon="tabler-upload"
              :loading="uploading"
              :disabled="uploading"
              @update:modelValue="handleTemplateFileSelect"
            />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect
              v-model="form.docx_template_path"
              :items="templateFiles"
              item-title="name"
              item-value="path"
              label="Выбрать DOCX из каталога"
              hide-details
              clearable
              :loading="filesLoading"
              no-data-text="Нет файлов"
            />
          </VCol>
          <VCol cols="12" md="6" class="d-flex align-center">
            <VSwitch v-model="form.is_active" label="Активен" hide-details />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VCard>
      <VCardText>
        <div class="text-sm font-semibold mb-3">Типы товаров</div>
        <div v-if="loading" class="text-sm text-muted">Загрузка...</div>
        <VRow v-else>
          <VCol
            v-for="type in productTypes"
            :key="type.id"
            cols="12"
            sm="6"
            md="4"
          >
            <VCheckbox
              v-model="form.product_type_ids"
              :value="type.id"
              :label="type.name"
              hide-details
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VCard>
      <VCardText>
        <div class="text-sm font-semibold mb-3">Предоплата</div>
        <VRow>
          <VCol cols="12">
            <VRadioGroup v-model="form.advance_mode" inline>
              <VRadio label="Нет" value="none" />
              <VRadio label="Процент" value="percent" />
              <VRadio label="По типам товаров" value="product_types" />
            </VRadioGroup>
          </VCol>
          <VCol v-if="form.advance_mode === 'percent'" cols="12" md="4">
            <VTextField
              v-model.number="form.advance_percent"
              type="number"
              min="0"
              max="100"
              label="Процент предоплаты"
              suffix="%"
              hide-details
            />
          </VCol>
          <VCol v-if="form.advance_mode === 'product_types'" cols="12">
            <VSelect
              v-model="form.advance_product_type_ids"
              :items="productTypes"
              item-title="name"
              item-value="id"
              label="Типы товаров для предоплаты"
              multiple
              chips
              hide-details
              no-data-text="Нет типов"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
  </div>
</template>




