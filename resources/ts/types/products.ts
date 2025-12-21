export type Product = {
  id: number
  tenant_id?: number
  company_id?: number
  name: string
  product_type_id?: number
  spending_type_id?: number
  scu?: string
  category_id?: number
  sub_category_id?: number
  brand_id?: number
  price?: number
  price_sale?: number
  price_vendor?: number
  price_zakup?: number
  delivery_price?: number
  montaj?: number
  montaj_sebest?: number
  category?: { id?: number; name?: string }
  sub_category?: { id?: number; name?: string }
  brand?: { id?: number; name?: string }
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
