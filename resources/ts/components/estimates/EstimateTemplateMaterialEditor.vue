<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import AutoComplete from 'primevue/autocomplete'
import { $api } from '@/utils/api'
import type { EstimateTemplateMaterial, EstimateTemplateMaterialItem } from '@/types/estimate-templates'
import type { Product } from '@/types/products'

const props = defineProps<{ templateId?: number | null }>()

const router = useRouter()
const route = useRoute()

const templateId = ref<number | null>(props.templateId ?? null)
const saving = ref(false)
const loading = ref(false)
const errorMessage = ref('')

const form = reactive({
  title: '',
})

const items = ref<EstimateTemplateMaterialItem[]>([])
const productSearch = ref<Product | string | null>(null)
const productSuggestions = ref<Product[]>([])
const productLoading = ref(false)

const loadTemplate = async () => {
  if (!templateId.value) return
  loading.value = true
  errorMessage.value = ''
  try {
    const response = await $api(`/estimate-templates/materials/${templateId.value}`)
    const data = response?.data as EstimateTemplateMaterial | undefined
    if (data) {
      form.title = data.title
      items.value = data.items?.length
        ? data.items.map(item => ({
          ...item,
          product_name: item.product_name ?? null,
          product_id: item.product_id ?? null,
        }))
        : []
    }
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось загрузить шаблон.'
  } finally {
    loading.value = false
  }
}

const addRow = () => {
  items.value.push({
    scu: '',
    count: 1,
    product_name: null,
    product_id: null,
  })
}

const removeRow = (index: number) => {
  items.value.splice(index, 1)
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
  items.value.push({
    scu: product.scu,
    count: 1,
    product_name: product.name,
    product_id: product.id,
  })
}

const handleProductSelect = (event: { value: Product }) => {
  addRowFromProduct(event.value)
  productSearch.value = null
}

const syncProductByScu = async (item: EstimateTemplateMaterialItem) => {
  const scu = item.scu?.trim()
  if (!scu) {
    item.product_name = null
    item.product_id = null
    return
  }

  try {
    const response = await $api('/products', {
      query: {
        q: scu,
        per_page: 10,
      },
    })
    const products = response?.data as Product[] | undefined
    if (!products?.length) {
      item.product_name = null
      item.product_id = null
      return
    }

    const match = products.find(product => product.scu?.toLowerCase() === scu.toLowerCase()) ?? products[0]
    item.product_name = match?.name ?? null
    item.product_id = match?.id ?? null
  } catch (error) {
    item.product_name = item.product_name ?? null
    item.product_id = item.product_id ?? null
  }
}

const saveTemplate = async () => {
  if (!form.title.trim()) {
    errorMessage.value = 'Название шаблона обязательно.'
    return
  }
  if (!items.value.length) {
    errorMessage.value = 'Добавьте хотя бы одну позицию.'
    return
  }

  saving.value = true
  errorMessage.value = ''
  try {
    const payload = {
      title: form.title,
      items: items.value
        .filter(item => item.scu.trim() !== '')
        .map(item => ({
          scu: item.scu.trim(),
          count: item.count,
        })),
    }

    if (templateId.value) {
      await $api(`/estimate-templates/materials/${templateId.value}`, {
        method: 'PATCH',
        body: payload,
      })
    } else {
      const response = await $api('/estimate-templates/materials', {
        method: 'POST',
        body: payload,
      })
      const data = response?.data as EstimateTemplateMaterial | undefined
      if (data?.id) {
        templateId.value = data.id
        await router.replace({ path: `/estimate-templates/materials/${data.id}` })
      }
    }
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось сохранить шаблон.'
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  if (templateId.value || route.params.id) {
    templateId.value = templateId.value ?? Number(route.params.id)
    await loadTemplate()
  } else if (!items.value.length) {
    addRow()
  }
})
</script>

<template>
  <div class="flex flex-column gap-4">
    <div class="flex items-center justify-between gap-3">
      <h2 class="text-xl font-semibold">
        {{ templateId ? `Шаблон материалов #${templateId}` : 'Новый шаблон материалов' }}
      </h2>
      <Button
        label="Сохранить"
        icon="pi pi-save"
        :loading="saving"
        @click="saveTemplate"
      />
    </div>

    <Card>
      <template #title>Название</template>
      <template #content>
        <InputText v-model="form.title" class="w-full" placeholder="Название шаблона" />
      </template>
    </Card>

    <Card>
      <template #title>Позиции</template>
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
        <DataTable :value="items" class="p-datatable-sm" dataKey="scu" :loading="loading" stripedRows>
          <Column field="scu" header="SKU" style="inline-size: 20ch;">
            <template #body="{ data }">
              <span>{{ data.scu }}</span>
            </template>
          </Column>
          <Column field="product_name" header="Название" style="inline-size: 40ch;">
            <template #body="{ data }">
              <span>{{ data.product_name || '—' }}</span>
            </template>
          </Column>
          <Column field="count" header="Кол-во" style="inline-size: 12ch;">
            <template #body="{ data }">
              <InputNumber
                v-model="data.count"
                class="w-full"
                :min="0"
                :step="1"
                showButtons
                buttonLayout="horizontal"
                incrementButtonIcon="pi pi-plus"
                decrementButtonIcon="pi pi-minus"
              />
            </template>
          </Column>
          <Column header="" style="inline-size: 6ch;">
            <template #body="{ index }">
              <Button
                icon="pi pi-trash"
                text
                severity="danger"
                aria-label="Удалить строку"
                @click="removeRow(index)"
              />
            </template>
          </Column>
          <template #empty>
            <div class="text-center py-6 text-muted">Добавьте позиции.</div>
          </template>
        </DataTable>
        <div class="mt-3">
          <Button
            label="Добавить строку"
            icon="pi pi-plus"
            text
            @click="addRow"
          />
        </div>
      </template>
    </Card>
  </div>
</template>
