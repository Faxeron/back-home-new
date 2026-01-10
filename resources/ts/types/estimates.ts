export interface EstimateItemProduct {
  id: number
  name: string | null
  scu: string | null
  product_type_id?: number | null
  unit?: {
    id?: number | null
    code?: string | null
    name?: string | null
  } | null
}

export interface EstimateItemGroup {
  id: number
  name: string | null
}

export interface EstimateItem {
  id: number
  estimate_id: number
  product_id: number
  qty: number
  qty_auto?: number
  qty_manual?: number
  price: number
  total: number
  group_id?: number | null
  sort_order?: number | null
  product?: EstimateItemProduct | null
  group?: EstimateItemGroup | null
}

export interface Estimate {
  id: number
  client_id?: number | null
  client_name?: string | null
  client_phone?: string | null
  site_address?: string | null
  created_by?: number | null
  counterparty?: {
    id: number
    type?: string | null
    name?: string | null
    phone?: string | null
    email?: string | null
  } | null
  creator?: {
    id: number
    name?: string | null
    email?: string | null
  } | null
  link?: string | null
  link_montaj?: string | null
  total_sum?: number
  items_count?: number
  created_at?: string | null
  updated_at?: string | null
  items?: EstimateItem[]
}
