<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useAbility } from '@casl/vue'
import { useRouter } from 'vue-router'
import ProductsPriceTable from '@/modules/products/components/ProductsPriceTable.vue'
import { useProductFilters } from '@/modules/products/composables/useProductFilters'
import { useProductLookups } from '@/modules/products/composables/useProductLookups'
import { useTableInfinite } from '@/composables/useTableLazy'
import { $api } from '@/utils/api'
import type { Product } from '@/modules/products/types/products.types'

const router = useRouter()
const tableRef = ref<any>(null)
const scrollHeight = ref('700px')
const reloadRef = ref<() => void>(() => {})
const errorMessage = ref('')
const importErrors = ref<string[]>([])
const saving = reactive<Record<number, boolean>>({})
const importInputRef = ref<HTMLInputElement | null>(null)
const importLoading = ref(false)
const exportLoading = ref(false)
const templateLoading = ref(false)
const snackbarOpen = ref(false)
const snackbarText = ref('')
const snackbarColor = ref<'success' | 'error'>('success')
const ability = useAbility()
const canEdit = computed(() => ability.can('edit', 'products'))
const canImport = computed(() => ability.can('create', 'pricebook'))
const canExport = computed(() => ability.can('export', 'pricebook'))

const showSnackbar = (text: string, color: 'success' | 'error' = 'success') => {
  snackbarText.value = text
  snackbarColor.value = color
  snackbarOpen.value = true
}

const {
  search,
  categoryId,
  subCategoryId,
  brandId,
  serverParams,
  resetFilters,
  handleSort,
} = useProductFilters({
  onChange: () => reloadRef.value(),
})

const {
  categories,
  subcategories,
  brands,
  loadCategories,
  loadBrands,
  loadSubcategories,
} = useProductLookups()

const {
  data,
  total: totalRecords,
  loading,
  reset: resetData,
  virtualScrollerOptions,
} = useTableInfinite<Product>({
  endpoint: 'products',
  perPage: 200,
  rowHeight: 52,
  params: () => ({ ...serverParams.value, include_global: false }),
})

reloadRef.value = () => {
  resetData()
}

const updateScrollHeight = () => {
  const tableEl = tableRef.value?.$el as HTMLElement | undefined
  if (!tableEl) return
  const rect = tableEl.getBoundingClientRect()
  const padding = 24
  const nextHeight = Math.max(320, window.innerHeight - rect.top - padding)
  scrollHeight.value = `${Math.floor(nextHeight)}px`
}

const handleResize = () => {
  updateScrollHeight()
}

watch(categoryId, async () => {
  subCategoryId.value = null
  await loadSubcategories(categoryId.value)
})

const openProduct = (row: Product) => {
  router.push({ path: `/products/${row.id}` })
}

const normalizeNumber = (value: any) => {
  if (value === '' || value === null || value === undefined) return null
  const numberValue = Number(String(value).replace(',', '.'))
  return Number.isNaN(numberValue) ? null : numberValue
}

const updateProduct = async (row: Product, payload: Record<string, any>) => {
  if (saving[row.id]) return
  saving[row.id] = true
  errorMessage.value = ''

  try {
    await $api(`products/${row.id}`, {
      method: 'PATCH',
      body: payload,
    })
    return true
  } catch (error) {
    console.error(error)
    errorMessage.value = 'Не удалось сохранить изменения. Проверьте соединение.'
    return false
  } finally {
    saving[row.id] = false
  }
}

const numericFields = new Set([
  'price',
  'price_sale',
  'price_vendor',
  'price_vendor_min',
  'price_zakup',
  'price_delivery',
  'montaj',
  'montaj_sebest',
])

const handleUpdateField = async (payload: { row: Product; field: keyof Product; value: any }) => {
  if (!canEdit.value) return
  const { row, field, value } = payload
  if (row.is_global && numericFields.has(String(field))) {
    errorMessage.value = 'Глобальные товары нельзя менять в прайсе.'
    return
  }
  const previousValue = row[field]
  const nextValue = numericFields.has(String(field)) ? normalizeNumber(value) : value
  row[field] = nextValue as any
  const ok = await updateProduct(row, { [field]: nextValue })
  if (!ok)
    row[field] = previousValue as any
}

const handleUpdateFlag = async (payload: { row: Product; field: 'is_visible' | 'is_top' | 'is_new'; value: boolean }) => {
  if (!canEdit.value) return
  const { row, field, value } = payload
  if (row.is_global && numericFields.has(String(field))) {
    errorMessage.value = 'Глобальные товары нельзя менять в прайсе.'
    return
  }
  const previousValue = row[field]
  row[field] = value
  const ok = await updateProduct(row, { [field]: value })
  if (!ok)
    row[field] = previousValue
}

const parseFileName = (value: string | null) => {
  if (!value) return null
  const match = /filename\*=(?:UTF-8''|)([^;]+)|filename="?([^";]+)"?/i.exec(value)
  const raw = match?.[1] ?? match?.[2]
  if (!raw) return null
  try {
    return decodeURIComponent(raw)
  } catch (error) {
    return raw
  }
}

const downloadFile = async (endpoint: string, fallbackName: string, setLoading: (value: boolean) => void) => {
  setLoading(true)
  errorMessage.value = ''
  try {
    const accessToken = useCookie('accessToken').value
    const baseUrl = import.meta.env.VITE_API_BASE_URL || '/api'
    const url = `${baseUrl}/${endpoint}`
    const response = await fetch(url, {
      headers: accessToken ? { Authorization: `Bearer ${accessToken}` } : {},
    })
    if (!response.ok)
      throw new Error('Download failed')

    const contentType = response.headers.get('content-type') || ''
    if (contentType.includes('text/html') || contentType.includes('application/json')) {
      throw new Error('Unexpected response')
    }

    const blob = await response.blob()
    const fileName = parseFileName(response.headers.get('content-disposition')) ?? fallbackName
    const link = document.createElement('a')
    const blobUrl = URL.createObjectURL(blob)
    link.href = blobUrl
    link.download = fileName
    document.body.appendChild(link)
    link.click()
    window.setTimeout(() => URL.revokeObjectURL(blobUrl), 1500)
    link.remove()
    showSnackbar('Файл подготовлен.', 'success')
  } catch (error: any) {
    console.error(error)
    showSnackbar('Не удалось скачать файл.', 'error')
  } finally {
    setLoading(false)
  }
}

const handleExport = async () => {
  if (!canExport.value) return
  await downloadFile('products/pricebook/export', 'pricebook_export.xlsx', value => {
    exportLoading.value = value
  })
}

const handleTemplate = async () => {
  if (!canExport.value) return
  await downloadFile('products/pricebook/template', 'pricebook_template.xlsx', value => {
    templateLoading.value = value
  })
}

const handleImportClick = () => {
  if (!canImport.value) return
  importInputRef.value?.click()
}

const handleImportFile = async (event: Event) => {
  const input = event.target as HTMLInputElement | null
  const file = input?.files?.[0]
  if (!file) return
  await importPricebook(file)
  if (input)
    input.value = ''
}

const importPricebook = async (file: File) => {
  if (!canImport.value) return
  importLoading.value = true
  errorMessage.value = ''
  importErrors.value = []
  try {
    const formData = new FormData()
    formData.append('file', file)
    const response = await $api('products/pricebook/import', {
      method: 'POST',
      body: formData,
    })
    const result = response?.data ?? response
    const created = result?.created ?? result?.data?.created ?? 0
    const updated = result?.updated ?? result?.data?.updated ?? 0
    const archived = result?.archived ?? result?.data?.archived ?? 0
    showSnackbar(`Импорт завершен. Создано: ${created}, обновлено: ${updated}, архивировано: ${archived}.`, 'success')
    reloadRef.value()
  } catch (error: any) {
    const data = error?.data ?? error?.response?.data
    const errors = Array.isArray(data?.errors) ? data.errors : []
    importErrors.value = errors
    errorMessage.value = errors.length
      ? 'Импорт не выполнен. Исправьте ошибки ниже.'
      : (data?.message ?? 'Не удалось импортировать прайс.')
    showSnackbar('Импорт не выполнен. Проверьте ошибки.', 'error')
  } finally {
    importLoading.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadCategories(), loadBrands(), loadSubcategories(categoryId.value)])
  await resetData()
  await nextTick()
  updateScrollHeight()
  window.addEventListener('resize', handleResize)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
})
</script>

<template>
  <div>
    <input
      ref="importInputRef"
      type="file"
      accept=".xlsx"
      class="d-none"
      @change="handleImportFile"
    />
    <ProductsPriceTable
      ref="tableRef"
      v-model:search="search"
      v-model:categoryId="categoryId"
      v-model:subCategoryId="subCategoryId"
      v-model:brandId="brandId"
      :rows="data"
      :loading="loading"
      :totalRecords="totalRecords"
      :scrollHeight="scrollHeight"
      :virtualScrollerOptions="virtualScrollerOptions"
      :categories="categories"
      :subcategories="subcategories"
      :brands="brands"
      :errorMessage="errorMessage"
      :importErrors="importErrors"
      :importing="importLoading"
      :exporting="exportLoading"
      :templating="templateLoading"
      :canEdit="canEdit"
      :canImport="canImport"
      :canExport="canExport"
      :canTemplate="canExport"
      @sort="handleSort"
      @reset="resetFilters"
      @open="openProduct"
      @update-field="handleUpdateField"
      @update-flag="handleUpdateFlag"
      @import="handleImportClick"
      @export="handleExport"
      @template="handleTemplate"
    />
    <VSnackbar v-model="snackbarOpen" :color="snackbarColor" location="bottom end" :timeout="2500">
      {{ snackbarText }}
    </VSnackbar>
  </div>
</template>
