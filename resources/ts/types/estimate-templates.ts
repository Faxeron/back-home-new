export interface EstimateTemplateMaterialItem {
  scu: string
  count: number
  product_id?: number | null
  product_name?: string | null
}

export interface EstimateTemplateMaterial {
  id: number
  title: string
  items: EstimateTemplateMaterialItem[]
  created_at?: string | null
  updated_at?: string | null
}

export interface EstimateTemplateSeptik {
  id: number
  title: string
  skus: string[]
  items?: EstimateTemplateSeptikItem[]
  template_ids?: number[]
  template_titles?: string[]
  template_id?: number | null
  template_title?: string | null
  created_at?: string | null
  updated_at?: string | null
}

export interface EstimateTemplateSeptikItem {
  scu: string
  product_id?: number | null
  product_name?: string | null
}
