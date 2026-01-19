import { $api } from '@/utils/api'
import type { Estimate, EstimateItem } from '@/modules/estimates/types/estimates.types'
import type { Product } from '@/modules/products/types/products.types'

export type CounterpartyMatch = {
  id: number
  name?: string | null
  phone?: string | null
  phone_normalized?: string | null
  type?: string | null
}

export type CounterpartyDetails = {
  id: number
  type?: string | null
  name?: string | null
  phone?: string | null
  email?: string | null
  individual?: {
    first_name?: string | null
    last_name?: string | null
    patronymic?: string | null
    passport_series?: string | null
    passport_number?: string | null
    passport_code?: string | null
    passport_whom?: string | null
    issued_at?: string | null
    issued_by?: string | null
    passport_address?: string | null
  } | null
  company?: {
    legal_name?: string | null
    short_name?: string | null
    inn?: string | null
    kpp?: string | null
    ogrn?: string | null
    legal_address?: string | null
    postal_address?: string | null
    director_name?: string | null
    accountant_name?: string | null
    bank_name?: string | null
    bik?: string | null
    account_number?: string | null
    correspondent_account?: string | null
  } | null
}

export const fetchEstimate = async (id: number): Promise<Estimate | undefined> => {
  const response = await $api(`/estimates/${id}`)
  return response?.data as Estimate | undefined
}

export const createEstimate = async (payload: Record<string, any>): Promise<Estimate | undefined> => {
  const response = await $api('/estimates', {
    method: 'POST',
    body: payload,
  })
  return response?.data as Estimate | undefined
}

export const updateEstimate = async (id: number, payload: Record<string, any>): Promise<Estimate | undefined> => {
  const response = await $api(`/estimates/${id}`, {
    method: 'PATCH',
    body: payload,
  })
  return response?.data as Estimate | undefined
}

export const deleteEstimate = async (id: number): Promise<void> => {
  await $api(`/estimates/${id}`, { method: 'DELETE' })
}

export const searchProducts = async (query: string): Promise<Product[]> => {
  const response = await $api('/products', {
    query: {
      q: query,
      per_page: 10,
    },
  })
  return response?.data ?? []
}

export const fetchEstimateTemplatesBySku = async (sku: string): Promise<any[]> => {
  const response = await $api('/estimate-templates/septiks', {
    query: {
      sku,
      per_page: 50,
    },
  })
  return Array.isArray(response?.data) ? response.data : []
}

export const addEstimateItem = async (
  estimateId: number,
  payload: { scu: string; qty: number | null },
): Promise<void> => {
  await $api(`/estimates/${estimateId}/items`, {
    method: 'POST',
    body: payload,
  })
}

export const applyEstimateTemplate = async (
  estimateId: number,
  payload: { root_scu: string; root_qty: number | null; template_id: number },
): Promise<EstimateItem[] | undefined> => {
  const response = await $api(`/estimates/${estimateId}/apply-template`, {
    method: 'POST',
    body: payload,
  })
  return response?.data as EstimateItem[] | undefined
}

export const updateEstimateItem = async (
  estimateId: number,
  itemId: number,
  payload: { qty?: number | null; price?: number | null },
): Promise<void> => {
  await $api(`/estimates/${estimateId}/items/${itemId}`, {
    method: 'PATCH',
    body: payload,
  })
}

export const lookupCounterparties = async (
  phonePrefix: string,
  limit = 10,
): Promise<CounterpartyMatch[]> => {
  const response = await $api('finance/counterparties', {
    query: {
      phone_prefix: phonePrefix,
      limit,
    },
  })
  return response?.data ?? []
}

export const fetchCounterpartyDetails = async (id: number): Promise<CounterpartyDetails | undefined> => {
  const response = await $api(`/finance/counterparties/${id}`)
  return response?.data as CounterpartyDetails | undefined
}

export const createEstimateContracts = async (
  estimateId: number,
  payload: Record<string, any>,
): Promise<any> => {
  const response = await $api(`/estimates/${estimateId}/contracts`, {
    method: 'POST',
    body: payload,
  })
  return response?.data
}
