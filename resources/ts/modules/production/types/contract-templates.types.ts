export type ProductTypeOption = {
  id: number
  name: string
  code?: string | null
}

export type ContractTemplate = {
  id: number
  tenant_id?: number
  company_id?: number
  name: string
  short_name: string
  docx_template_path?: string | null
  is_active?: boolean
  document_type?: 'supply' | 'install' | 'combined' | null
  advance_mode?: 'none' | 'percent' | 'product_types' | null
  advance_percent?: number | null
  advance_product_type_ids?: number[] | null
  product_types?: ProductTypeOption[]
  product_type_ids?: number[]
  created_at?: string
  updated_at?: string
}

export type ContractTemplatePayload = {
  name: string
  short_name: string
  docx_template_path?: string | null
  is_active?: boolean
  document_type?: 'supply' | 'install' | 'combined' | null
  advance_mode?: 'none' | 'percent' | 'product_types' | null
  advance_percent?: number | null
  advance_product_type_ids?: number[] | null
  product_type_ids: number[]
}

export type ContractTemplateFile = {
  name: string
  path: string
}
