<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import MultiSelect from 'primevue/multiselect'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import AutoComplete from 'primevue/autocomplete'
import { $api } from '@/utils/api'
import type { EstimateTemplateMaterial, EstimateTemplateSeptik, EstimateTemplateSeptikItem } from '@/modules/estimates/types/estimate-templates.types'
import type { Product } from '@/modules/products/types/products.types'

const props = defineProps<{ templateId?: number | null }>()

const router = useRouter()
const route = useRoute()

const templateId = ref<number | null>(props.templateId ?? null)
const saving = ref(false)
const loading = ref(false)
const errorMessage = ref('')

const form = reactive({
  title: '',
  template_ids: [] as number[],
})

const items = ref<EstimateTemplateSeptikItem[]>([])
const materialTemplates = ref<EstimateTemplateMaterial[]>([])
const productSearch = ref<Product | string | null>(null)
const productSuggestions = ref<Product[]>([])
const productLoading = ref(false)

const loadMaterials = async () => {
  try {
    const response = await $api('/estimate-templates/materials', { query: { per_page: 200 } })
    const list = response?.data as EstimateTemplateMaterial[] | undefined
    materialTemplates.value = Array.isArray(list) ? list : []
  } catch {
    materialTemplates.value = []
  }
}

const loadTemplate = async () => {
  if (!templateId.value) return
  loading.value = true
  errorMessage.value = ''
  try {
    const response = await $api(`/estimate-templates/septiks/${templateId.value}`)
    const data = response?.data as EstimateTemplateSeptik | undefined
    if (data) {
      form.title = data.title
      form.template_ids = Array.isArray(data.template_ids) && data.template_ids.length
        ? data.template_ids
        : (data.template_id ? [data.template_id] : [])
      if (Array.isArray(data.items) && data.items.length) {
        items.value = data.items.map(item => ({
          ...item,
          product_name: item.product_name ?? null,
          product_id: item.product_id ?? null,
        }))
      } else {
        items.value = Array.isArray(data.skus)
          ? data.skus.map(scu => ({ scu, product_name: null, product_id: null }))
          : []
      }
    }
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось загрузить связку.'
  } finally {
    loading.value = false
  }
}

const fetchProductSuggestions = async (query: string) => {
  const search = query.trim()
  if (!search) {
    productSuggestions.value = []
    return
  }

  productLoading.value = true
  try {
    const response = await $api('/products', {
      query: {
        q: search,
        per_page: 10,
      },
    })
    productSuggestions.value = response?.data ?? []
  } catch (error) {
    productSuggestions.value = []
  } finally {
    productLoading.value = false
  }
}

const handleProductSearch = async (event: { query: string }) => {
  await fetchProductSuggestions(event.query)
}

const addRowFromProduct = (product: Product) => {
  if (!product?.scu) return
  if (items.value.some(item => item.scu === product.scu)) return
  items.value.push({
    scu: product.scu,
    product_name: product.name,
    product_id: product.id,
  })
}

const handleProductSelect = (event: { value: Product }) => {
  addRowFromProduct(event.value)
  productSearch.value = null
}

const removeRow = (index: number) => {
  items.value.splice(index, 1)
}

const saveTemplate = async () => {
  if (!form.title.trim()) {
    errorMessage.value = 'Название обязательно.'
    return
  }
  if (!form.template_ids.length) {
    errorMessage.value = 'Выберите шаблон материалов.'
    return
  }
  const skus = items.value.map(item => item.scu).filter(Boolean)
  if (!skus.length) {
    errorMessage.value = 'Добавьте хотя бы один SKU.'
    return
  }

  saving.value = true
  errorMessage.value = ''
  try {
    const payload = {
      title: form.title,
      template_ids: form.template_ids,
      skus,
    }

    if (templateId.value) {
      await $api(`/estimate-templates/septiks/${templateId.value}`, {
        method: 'PATCH',
        body: payload,
      })
    } else {
      const response = await $api('/estimate-templates/septiks', {
        method: 'POST',
        body: payload,
      })
      const data = response?.data as EstimateTemplateSeptik | undefined
      if (data?.id) {
        templateId.value = data.id
        await router.replace({ path: `/estimate-templates/septiks/${data.id}` })
      }
    }
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось сохранить связку.'
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await loadMaterials()
  if (templateId.value || route.params.id) {
    templateId.value = templateId.value ?? Number(route.params.id)
    await loadTemplate()
  }
})
</script>

<template>
  <div class="flex flex-column gap-4">
    <div class="flex items-center justify-between gap-3">
      <h2 class="text-xl font-semibold">
        {{ templateId ? `Связка септиков #${templateId}` : 'Новая связка септиков' }}
      </h2>
      <Button
        label="Сохранить"
        icon="pi pi-save"
        :loading="saving"
        @click="saveTemplate"
      />
    </div>

    <Card>
      <template #title>Основные данные</template>
      <template #content>
        <div class="grid">
          <div class="col-12 md:col-6">
            <label class="text-sm text-muted">Название</label>
            <InputText v-model="form.title" class="w-full" placeholder="Название связки" />
          </div>
          <div class="col-12 md:col-6">
            <label class="text-sm text-muted">Шаблоны материалов</label>
            <MultiSelect
              v-model="form.template_ids"
              :options="materialTemplates"
              optionLabel="title"
              optionValue="id"
              placeholder="Выберите шаблоны"
              class="w-full"
              display="chip"
            />
          </div>
        </div>
      </template>
    </Card>

    <Card>
      <template #title>SKU в группе</template>
      <template #content>
        <div v-if="errorMessage" class="mb-3 text-sm" style="color: #dc2626">
          {{ errorMessage }}
        </div>
        <div class="mb-3 flex flex-column gap-2">
          <span class="text-sm text-muted">Добавить товар по названию</span>
          <AutoComplete
            v-model="productSearch"
            :suggestions="productSuggestions"
            optionLabel="name"
            :loading="productLoading"
            forceSelection
            class="w-full"
            placeholder="Начните вводить название"
            @complete="handleProductSearch"
            @item-select="handleProductSelect"
          />
        </div>
        <DataTable :value="items" class="p-datatable-sm" dataKey="scu" stripedRows>
          <Column field="scu" header="SKU" style="inline-size: 20ch;">
            <template #body="{ data }">
              <span>{{ data.scu }}</span>
            </template>
          </Column>
          <Column field="product_name" header="Название">
            <template #body="{ data }">
              <span>{{ data.product_name || '—' }}</span>
            </template>
          </Column>
          <Column header="" style="inline-size: 6ch;">
            <template #body="{ index }">
              <Button
                icon="pi pi-trash"
                text
                severity="danger"
                aria-label="Удалить позицию"
                @click="removeRow(index)"
              />
            </template>
          </Column>
          <template #empty>
            <div class="text-center py-6 text-muted">Добавьте позиции.</div>
          </template>
        </DataTable>
      </template>
    </Card>
  </div>
</template>
