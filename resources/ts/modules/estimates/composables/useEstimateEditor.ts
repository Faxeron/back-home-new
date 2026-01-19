import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import type { Estimate, EstimateItem } from '@/modules/estimates/types/estimates.types'
import type { Product } from '@/modules/products/types/products.types'
import {
  addEstimateItem,
  applyEstimateTemplate,
  createEstimate,
  fetchEstimate,
  fetchEstimateTemplatesBySku,
  lookupCounterparties,
  searchProducts,
  updateEstimate,
  updateEstimateItem,
} from '@/modules/estimates/api/estimate.api'
import type { CounterpartyMatch } from '@/modules/estimates/api/estimate.api'
import {
  DEFAULT_GROUP_LABEL,
  formatCurrency,
  getGroupOrder,
  getProductTypeLabel,
} from '@/modules/estimates/config/estimateTable.config'

type EstimateItemRow = EstimateItem & { groupLabel: string; typeLabel: string; groupOrder: number }
type TemplateOption = { id: number; title: string }

export const useEstimateEditor = (initialEstimateId?: number | null) => {
  const router = useRouter()
  const route = useRoute()

  const estimateId = ref<number | null>(initialEstimateId ?? null)
  const estimate = ref<Estimate | null>(null)

  const items = ref<EstimateItemRow[]>([])
  const loading = ref(false)
  const saving = ref(false)
  const errorMessage = ref('')

  const form = reactive({
    client_id: null as number | null,
    client_name: '',
    client_phone: '',
    site_address: '',
  })

  const addSku = ref('')
  const addQty = ref<number | null>(1)
  const productSuggestions = ref<Product[]>([])
  const productLoading = ref(false)
  const templateOptions = ref<TemplateOption[]>([])
  const templateCheckLoading = ref(false)
  const vuetifyProduct = ref<Product | null>(null)
  const vuetifySearch = ref('')

  const clientMatches = ref<CounterpartyMatch[]>([])
  const clientLookupLoading = ref(false)
  const clientLookupToken = ref(0)
  let clientLookupTimer: number | null = null
  const isAutoSettingClient = ref(false)
  const MIN_PHONE_PREFIX_LENGTH = 6
  const lockedPhone = ref<string | null>(null)

  const decorateItems = (rows: EstimateItem[]): EstimateItemRow[] =>
    rows.map(item => ({
      ...item,
      groupLabel: item.group?.name ?? DEFAULT_GROUP_LABEL,
      typeLabel: getProductTypeLabel(item.product?.product_type_id ?? null),
      groupOrder: getGroupOrder(item.product?.product_type_id ?? null),
    }))

  const itemsSorted = computed(() => {
    const list = items.value.slice()
    list.sort((a, b) => {
      if (a.groupOrder !== b.groupOrder) {
        return a.groupOrder - b.groupOrder
      }
      if (a.groupLabel !== b.groupLabel) {
        return a.groupLabel.localeCompare(b.groupLabel)
      }
      return (a.sort_order ?? 0) - (b.sort_order ?? 0)
    })
    return list
  })

  const groupTotals = computed(() => {
    const totals: Record<string, number> = {}
    for (const item of items.value) {
      const key = item.groupLabel
      totals[key] = (totals[key] ?? 0) + (item.total ?? 0)
    }
    return totals
  })

  const grandTotal = computed(() =>
    items.value.reduce((sum, item) => sum + (item.total ?? 0), 0),
  )

  const normalizePhonePrefix = (value?: string | null) => {
    const digits = (value ?? '').replace(/\D/g, '')
    if (!digits) return ''
    let normalized = digits
    if (normalized.startsWith('8')) normalized = `7${normalized.slice(1)}`
    if (!normalized.startsWith('7')) normalized = `7${normalized}`
    return normalized
  }

  const formatPhoneFromDigits = (value?: string | null) => {
    const digits = normalizePhonePrefix(value)
    if (!digits) return ''
    const parts = digits.split('')
    const chunk = (from: number, to: number) => parts.slice(from, to).join('')
    const result = [
      `+${parts[0] ?? '7'}`,
      chunk(1, 4),
      chunk(4, 7),
      chunk(7, 9),
      chunk(9, 11),
    ].filter(Boolean)
    return result.join(' ').trim()
  }

  const normalizedPhone = computed(() => normalizePhonePrefix(form.client_phone))
  const phonePrefix = computed(() => (normalizedPhone.value.startsWith('7')
    ? normalizedPhone.value.slice(1)
    : normalizedPhone.value))
  const isClientLocked = computed(() => Boolean(form.client_id))
  const showClientLookup = computed(() =>
    !isClientLocked.value && phonePrefix.value.length >= MIN_PHONE_PREFIX_LENGTH,
  )

  const buildPublicLink = (path?: string | null) => {
    if (!path) return null
    if (path.startsWith('http://') || path.startsWith('https://')) return path
    if (typeof window === 'undefined') return path
    const normalized = path.startsWith('/') ? path : `/${path}`
    return `${window.location.origin}${normalized}`
  }

  const clientLink = computed(() => buildPublicLink(estimate.value?.link ?? null))
  const montajLink = computed(() => {
    const baseLink = estimate.value?.link ?? null
    if (baseLink) {
      return buildPublicLink(`${baseLink}mnt`)
    }
    return buildPublicLink(estimate.value?.link_montaj ?? null)
  })
  const copiedKey = ref<'client' | 'montaj' | null>(null)
  let copyTimer: number | null = null

  const copyToClipboard = async (value: string | null, key: 'client' | 'montaj') => {
    if (!value) return
    const text = value.trim()
    if (!text) return
    try {
      if (typeof navigator !== 'undefined' && navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(text)
      } else if (typeof document !== 'undefined') {
        const textarea = document.createElement('textarea')
        textarea.value = text
        textarea.setAttribute('readonly', 'true')
        textarea.style.position = 'absolute'
        textarea.style.left = '-9999px'
        document.body.appendChild(textarea)
        textarea.select()
        document.execCommand('copy')
        document.body.removeChild(textarea)
      } else {
        return
      }
      copiedKey.value = key
      if (copyTimer) {
        window.clearTimeout(copyTimer)
      }
      copyTimer = window.setTimeout(() => {
        copiedKey.value = null
      }, 2000)
    } catch (error) {
      copiedKey.value = null
    }
  }

  const fetchClientMatches = async (prefix: string) => {
    if (!prefix) {
      clientMatches.value = []
      return
    }

    const token = ++clientLookupToken.value
    clientLookupLoading.value = true
    try {
      const matches = await lookupCounterparties(prefix, 10)
      if (clientLookupToken.value !== token) return
      clientMatches.value = matches
    } catch (error) {
      if (clientLookupToken.value !== token) return
      clientMatches.value = []
    } finally {
      if (clientLookupToken.value === token) {
        clientLookupLoading.value = false
      }
    }
  }

  const selectClient = (client: CounterpartyMatch) => {
    isAutoSettingClient.value = true
    form.client_id = client.id
    form.client_name = client.name ?? ''
    form.client_phone = formatPhoneFromDigits(client.phone_normalized ?? client.phone)
    lockedPhone.value = normalizePhonePrefix(form.client_phone)
    clientMatches.value = []
    isAutoSettingClient.value = false
  }

  const createNewClient = () => {
    form.client_id = null
    lockedPhone.value = null
    clientMatches.value = []
  }

  const clearSelectedClient = () => {
    form.client_id = null
    lockedPhone.value = null
    clientMatches.value = []
  }

  const loadEstimate = async (options: { preserveForm?: boolean } = {}) => {
    if (!estimateId.value) return
    loading.value = true
    errorMessage.value = ''
    try {
      const data = await fetchEstimate(estimateId.value)
      if (!data) return
      estimate.value = data
      const preserveForm = Boolean(options.preserveForm)
      const incomingClientId = data.client_id ?? null
      const incomingName = data.client_name ?? ''
      const incomingPhone = formatPhoneFromDigits(data.client_phone)
      const incomingAddress = data.site_address ?? ''

      if (!preserveForm || incomingClientId !== null || form.client_id === null) {
        form.client_id = incomingClientId
      }
      if (!preserveForm || incomingName || !form.client_name) {
        form.client_name = incomingName
      }
      if (!preserveForm || incomingPhone || !form.client_phone) {
        form.client_phone = incomingPhone
      }
      if (!preserveForm || incomingAddress || !form.site_address) {
        form.site_address = incomingAddress
      }
      if (form.client_id) {
        lockedPhone.value = normalizePhonePrefix(form.client_phone)
      } else if (!preserveForm) {
        lockedPhone.value = null
      }
      items.value = decorateItems(data.items ?? [])
    } catch (error: any) {
      errorMessage.value = error?.response?.data?.message ?? 'Не удалось загрузить смету.'
    } finally {
      loading.value = false
    }
  }

  const ensureEstimate = async (isDraft = true, options: { skipRouteReplace?: boolean } = {}) => {
    if (estimateId.value) return estimateId.value
    if (!isDraft && !form.client_name.trim()) {
      errorMessage.value = 'Имя клиента обязательно.'
      return null
    }

    saving.value = true
    errorMessage.value = ''
    try {
      const data = await createEstimate({
        client_id: form.client_id ?? null,
        client_name: isDraft ? (form.client_name.trim() || null) : form.client_name,
        client_phone: form.client_phone || null,
        site_address: form.site_address || null,
        draft: isDraft,
      })
      if (data) {
        estimate.value = data
        estimateId.value = data.id
        if (!options.skipRouteReplace) {
          await router.replace({ path: `/estimates/${data.id}/edit` })
        }
        return data.id
      }
    } catch (error: any) {
      errorMessage.value = error?.response?.data?.message ?? 'Не удалось создать смету.'
    } finally {
      saving.value = false
    }

    return null
  }

  const saveEstimate = async () => {
    if (!form.client_name.trim()) {
      errorMessage.value = 'Имя клиента обязательно.'
      return
    }
    if (!estimateId.value) {
      await ensureEstimate(false)
      return
    }

    saving.value = true
    errorMessage.value = ''
    try {
      const data = await updateEstimate(estimateId.value, {
        client_id: form.client_id ?? null,
        client_name: form.client_name,
        client_phone: form.client_phone || null,
        site_address: form.site_address || null,
        draft: false,
      })
      if (data) {
        estimate.value = data
      }
    } catch (error: any) {
      errorMessage.value = error?.response?.data?.message ?? 'Не удалось сохранить смету.'
    } finally {
      saving.value = false
    }
  }

  const refreshItems = async () => {
    if (!estimateId.value) return
    await loadEstimate({ preserveForm: true })
  }

  const fetchProductSuggestions = async (query: string) => {
    const search = query.trim()
    if (!search) {
      productSuggestions.value = []
      return
    }

    productLoading.value = true
    try {
      productSuggestions.value = await searchProducts(search)
    } catch (error) {
      productSuggestions.value = []
    } finally {
      productLoading.value = false
    }
  }

  const checkTemplateForSku = async (sku?: string | null) => {
    const normalized = sku?.trim() ?? ''
    if (!normalized) {
      templateOptions.value = []
      return
    }

    templateCheckLoading.value = true
    try {
      const list = await fetchEstimateTemplatesBySku(normalized)
      const map = new Map<number, string>()

      for (const row of list as any[]) {
        const ids = Array.isArray(row?.template_ids)
          ? row.template_ids
          : row?.template_id
            ? [row.template_id]
            : []
        const titles = Array.isArray(row?.template_titles)
          ? row.template_titles
          : row?.template_title
            ? [row.template_title]
            : []

        ids.forEach((id: any, index: number) => {
          const parsedId = Number(id)
          if (!Number.isFinite(parsedId)) return
          const title = titles[index] ?? titles[0] ?? `#${parsedId}`
          if (!map.has(parsedId)) map.set(parsedId, title || `#${parsedId}`)
        })
      }

      templateOptions.value = Array.from(map.entries()).map(([id, title]) => ({
        id,
        title,
      }))
    } catch (error) {
      templateOptions.value = []
    } finally {
      templateCheckLoading.value = false
    }
  }

  const handleVuetifySelect = async (value: Product | null) => {
    if (!value) {
      clearSelectedProduct()
      return
    }
    addSku.value = value?.scu ?? ''
    await checkTemplateForSku(value?.scu)
  }

  const handleVuetifySearch = async (value: string) => {
    await fetchProductSuggestions(value)
  }

  const clearSelectedProduct = () => {
    addSku.value = ''
    templateOptions.value = []
    vuetifyProduct.value = null
    vuetifySearch.value = ''
  }

  const addItemBySku = async () => {
    const id = await ensureEstimate(true, { skipRouteReplace: true })
    if (!id) return
    if (!addSku.value.trim()) return

    saving.value = true
    errorMessage.value = ''
    try {
      await addEstimateItem(id, {
        scu: addSku.value.trim(),
        qty: addQty.value ?? 1,
      })
      addSku.value = ''
      addQty.value = 1
      clearSelectedProduct()
      await refreshItems()
    } catch (error: any) {
      errorMessage.value = error?.response?.data?.message ?? 'Не удалось добавить позицию.'
    } finally {
      saving.value = false
    }
  }

  const applyTemplateOnly = async (templateId: number) => {
    const id = await ensureEstimate(true, { skipRouteReplace: true })
    if (!id) return
    if (!addSku.value.trim()) return

    saving.value = true
    errorMessage.value = ''
    try {
      const data = await applyEstimateTemplate(id, {
        root_scu: addSku.value.trim(),
        root_qty: addQty.value ?? 1,
        template_id: templateId,
      })
      if (data) {
        items.value = decorateItems(data)
      } else {
        await refreshItems()
      }
      templateOptions.value = templateOptions.value.filter(option => option.id !== templateId)
    } catch (error: any) {
      errorMessage.value = error?.response?.data?.message ?? 'Не удалось применить шаблон.'
    } finally {
      saving.value = false
    }
  }

  const updateItemQty = async (item: EstimateItemRow) => {
    if (!estimateId.value) return
    saving.value = true
    errorMessage.value = ''
    try {
      await updateEstimateItem(estimateId.value, item.id, {
        qty: item.qty,
      })
      await refreshItems()
    } catch (error: any) {
      errorMessage.value = error?.response?.data?.message ?? 'Не удалось обновить количество.'
    } finally {
      saving.value = false
    }
  }

  const updateItemPrice = async (item: EstimateItemRow) => {
    if (!estimateId.value) return
    saving.value = true
    errorMessage.value = ''
    try {
      await updateEstimateItem(estimateId.value, item.id, {
        price: item.price,
      })
      await refreshItems()
    } catch (error: any) {
      errorMessage.value = error?.response?.data?.message ?? 'Не удалось обновить цену.'
    } finally {
      saving.value = false
    }
  }

  onMounted(async () => {
    if (estimateId.value || route.params.id) {
      estimateId.value = estimateId.value ?? Number(route.params.id)
      await loadEstimate()
    }
  })

  watch(phonePrefix, value => {
    if (isAutoSettingClient.value) return
    if (form.client_id && lockedPhone.value && normalizedPhone.value === lockedPhone.value) {
      return
    }
    if (form.client_id) {
      form.client_id = null
      lockedPhone.value = null
    }
    if (value.length < MIN_PHONE_PREFIX_LENGTH) {
      clientMatches.value = []
      return
    }
    if (clientLookupTimer) {
      window.clearTimeout(clientLookupTimer)
    }
    clientLookupTimer = window.setTimeout(() => {
      fetchClientMatches(normalizedPhone.value)
    }, 350)
  })

  return {
    estimateId,
    estimate,
    form,
    items,
    itemsSorted,
    groupTotals,
    grandTotal,
    loading,
    saving,
    errorMessage,
    formatCurrency,
    formatPhoneFromDigits,
    addSku,
    addQty,
    productSuggestions,
    productLoading,
    templateOptions,
    templateCheckLoading,
    vuetifyProduct,
    vuetifySearch,
    handleVuetifySearch,
    handleVuetifySelect,
    addItemBySku,
    applyTemplateOnly,
    updateItemQty,
    updateItemPrice,
    clientMatches,
    clientLookupLoading,
    isClientLocked,
    showClientLookup,
    selectClient,
    createNewClient,
    clearSelectedClient,
    clientLink,
    montajLink,
    copiedKey,
    copyToClipboard,
    saveEstimate,
  }
}
