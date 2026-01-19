export type Product = {
  id: number
  tenant_id?: number
  company_id?: number
  name: string
  product_type_id?: number
  product_kind_id?: number
  spending_type_id?: number
  scu?: string
  sort_order?: number
  category_id?: number
  sub_category_id?: number
  brand_id?: number
  price?: number
  price_sale?: number
  price_vendor?: number
  price_vendor_min?: number
  price_zakup?: number
  price_delivery?: number
  montaj?: number
  montaj_sebest?: number
  is_global?: boolean
  unit_id?: number
  is_visible?: boolean
  is_top?: boolean
  is_new?: boolean
  created_at?: string
  updated_at?: string
  category?: { id?: number; name?: string }
  sub_category?: { id?: number; name?: string }
  brand?: { id?: number; name?: string }
  kind?: { id?: number; name?: string }
  description?: ProductDescription
  media?: ProductMedia[]
  attributes?: ProductAttributeValue[]
  relations?: ProductRelation[]
}

export type ProductCategory = {
  id: number
  name: string
  products_count?: number
}

export type ProductSubcategory = {
  id: number
  name: string
  category_id?: number
  products_count?: number
  category?: { id?: number; name?: string }
}

export type ProductBrand = {
  id: number
  name: string
  products_count?: number
}

export type ProductKind = {
  id: number
  name: string
}

export type ProductType = {
  id: number
  name: string
  code?: string | null
}

export type ProductDescription = {
  description_short?: string | null
  description_long?: string | null
  dignities?: string | null
  constructive?: string | null
  avito1?: string | null
  avito2?: string | null
}

export type ProductMedia = {
  id: number
  type?: string
  url?: string
  sort_order?: number
}

export type ProductAttributeValue = {
  id: number
  attribute_id?: number
  name?: string
  value_string?: string | null
  value_number?: number | null
}

export type ProductRelation = {
  id: number
  relation_type?: string
  related_product?: {
    id: number
    name?: string
    scu?: string
  } | null
}
