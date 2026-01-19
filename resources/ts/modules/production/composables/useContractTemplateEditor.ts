import { onMounted, reactive, ref } from 'vue'
import type { ContractTemplate, ContractTemplateFile } from '@/modules/production/types/contract-templates.types'
import type { ProductType } from '@/modules/products/types/products.types'
import {
  createContractTemplate,
  fetchContractTemplateFiles,
  fetchContractTemplate,
  updateContractTemplate,
  uploadContractTemplateFile,
} from '@/modules/production/api/contractTemplates.api'
import { $api } from '@/utils/api'

export const useContractTemplateEditor = (initialId?: number | null) => {
  const templateId = ref<number | null>(initialId ?? null)
  const loading = ref(false)
  const saving = ref(false)
  const errorMessage = ref('')

  const form = reactive({
    name: '',
    short_name: '',
    docx_template_path: '',
    is_active: true,
    product_type_ids: [] as number[],
    document_type: 'combined' as 'supply' | 'install' | 'combined',
    advance_mode: 'none' as 'none' | 'percent' | 'product_types',
    advance_percent: null as number | null,
    advance_product_type_ids: [] as number[],
  })

  const productTypes = ref<ProductType[]>([])
  const templateFiles = ref<ContractTemplateFile[]>([])
  const filesLoading = ref(false)
  const uploading = ref(false)

  const loadProductTypes = async () => {
    try {
      const response = await $api('/products/types')
      productTypes.value = response?.data ?? []
    } catch (error) {
      productTypes.value = []
    }
  }

  const fillForm = (data: ContractTemplate) => {
    form.name = data.name ?? ''
    form.short_name = data.short_name ?? ''
    form.docx_template_path = data.docx_template_path ?? ''
    if (form.docx_template_path) {
      const existing = templateFiles.value.find(file => file.path === form.docx_template_path)
      if (!existing) {
        const name = form.docx_template_path.split('/').pop() ?? form.docx_template_path
        templateFiles.value = [...templateFiles.value, { name, path: form.docx_template_path }]
      }
    }
    form.is_active = data.is_active !== false
    const typeIds = data.product_type_ids ?? data.product_types?.map(type => type.id) ?? []
    form.product_type_ids = [...typeIds]
    form.document_type = data.document_type ?? 'combined'
    const modeFromData = data.advance_mode
      ?? (data.advance_percent != null
        ? 'percent'
        : data.advance_product_type_ids?.length
          ? 'product_types'
          : 'none')
    form.advance_mode = modeFromData === 'percent' || modeFromData === 'product_types' ? modeFromData : 'none'
    form.advance_percent = data.advance_percent ?? null
    form.advance_product_type_ids = data.advance_product_type_ids ? [...data.advance_product_type_ids] : []
  }

  const loadTemplate = async () => {
    if (!templateId.value) return
    loading.value = true
    errorMessage.value = ''
    try {
      const data = await fetchContractTemplate(templateId.value)
      if (data) {
        fillForm(data)
      }
    } catch (error: any) {
      errorMessage.value = error?.response?.data?.message ?? 'Не удалось загрузить шаблон.'
    } finally {
      loading.value = false
    }
  }

  const loadTemplateFiles = async () => {
    filesLoading.value = true
    try {
      templateFiles.value = await fetchContractTemplateFiles()
    } catch (error) {
      templateFiles.value = []
    } finally {
      filesLoading.value = false
    }
  }

  const uploadTemplateFile = async (file?: File | null) => {
    if (!file) return
    uploading.value = true
    errorMessage.value = ''
    try {
      const uploaded = await uploadContractTemplateFile(file)
      if (uploaded?.path) {
        form.docx_template_path = uploaded.path
        await loadTemplateFiles()
      }
    } catch (error: any) {
      errorMessage.value = error?.response?.data?.message ?? 'Не удалось загрузить файл.'
    } finally {
      uploading.value = false
    }
  }

  const saveTemplate = async () => {
    if (!form.name.trim()) {
      errorMessage.value = 'Название шаблона обязательно.'
      return null
    }
    if (!form.short_name.trim()) {
      errorMessage.value = 'Короткое название обязательно.'
      return null
    }
    if (!form.docx_template_path.trim()) {
      errorMessage.value = 'Добавьте или выберите DOCX-шаблон.'
      return null
    }
    if (!form.product_type_ids.length) {
      errorMessage.value = 'Выберите минимум один тип товара.'
      return null
    }
    if (form.advance_mode === 'percent' && (form.advance_percent === null || Number.isNaN(form.advance_percent))) {
      errorMessage.value = 'Укажите процент предоплаты.'
      return null
    }
    if (form.advance_mode === 'product_types' && !form.advance_product_type_ids.length) {
      errorMessage.value = 'Выберите типы товаров для предоплаты.'
      return null
    }

    saving.value = true
    errorMessage.value = ''
    try {
      const payload = {
        name: form.name.trim(),
        short_name: form.short_name.trim(),
        docx_template_path: form.docx_template_path.trim() || null,
        is_active: form.is_active,
        product_type_ids: form.product_type_ids,
        document_type: form.document_type,
        advance_mode: form.advance_mode,
        advance_percent: form.advance_mode === 'percent' ? form.advance_percent : null,
        advance_product_type_ids: form.advance_mode === 'product_types' ? form.advance_product_type_ids : [],
      }

      if (templateId.value) {
        const updated = await updateContractTemplate(templateId.value, payload)
        if (updated) {
          fillForm(updated)
        }
        return templateId.value
      }

      const created = await createContractTemplate(payload)
      if (created?.id) {
        templateId.value = created.id
        fillForm(created)
        return created.id
      }
    } catch (error: any) {
      errorMessage.value = error?.response?.data?.message ?? 'Не удалось сохранить шаблон.'
    } finally {
      saving.value = false
    }

    return null
  }

  onMounted(async () => {
    await loadProductTypes()
    await loadTemplateFiles()
    if (templateId.value) {
      await loadTemplate()
    }
  })

  return {
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
    loadTemplateFiles,
    uploadTemplateFile,
  }
}
