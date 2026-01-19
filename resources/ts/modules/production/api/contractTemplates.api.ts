import { $api } from '@/utils/api'
import type { ContractTemplate, ContractTemplateFile, ContractTemplatePayload } from '@/modules/production/types/contract-templates.types'

export const fetchContractTemplates = async (params?: Record<string, any>): Promise<{ data: ContractTemplate[]; meta?: any }> => {
  const response = await $api('/contract-templates', { query: params })
  return response as { data: ContractTemplate[]; meta?: any }
}

export const fetchContractTemplate = async (id: number): Promise<ContractTemplate | undefined> => {
  const response = await $api(`/contract-templates/${id}`)
  return response?.data as ContractTemplate | undefined
}

export const createContractTemplate = async (payload: ContractTemplatePayload): Promise<ContractTemplate | undefined> => {
  const response = await $api('/contract-templates', {
    method: 'POST',
    body: payload,
  })
  return response?.data as ContractTemplate | undefined
}

export const updateContractTemplate = async (
  id: number,
  payload: Partial<ContractTemplatePayload>,
): Promise<ContractTemplate | undefined> => {
  const response = await $api(`/contract-templates/${id}`, {
    method: 'PATCH',
    body: payload,
  })
  return response?.data as ContractTemplate | undefined
}

export const deleteContractTemplate = async (id: number): Promise<void> => {
  await $api(`/contract-templates/${id}`, { method: 'DELETE' })
}

export const fetchContractTemplateFiles = async (): Promise<ContractTemplateFile[]> => {
  const response = await $api('/contract-templates/files')
  return (response?.data ?? []) as ContractTemplateFile[]
}

export const uploadContractTemplateFile = async (file: File): Promise<ContractTemplateFile | undefined> => {
  const formData = new FormData()
  formData.append('file', file)

  const response = await $api('/contract-templates/files', {
    method: 'POST',
    body: formData,
  })

  return response?.data as ContractTemplateFile | undefined
}
