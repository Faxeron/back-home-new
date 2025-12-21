export type Money = {
  amount: string
  currency: string
}

export type Transaction = {
  id: number
  sum: Money
  is_paid: boolean
  date_is_paid?: string
  is_completed: boolean
  date_is_completed?: string
  cashbox_id?: number
  transaction_type_id?: number | string
  payment_method_id?: number | string
  company_id?: number
  counterparty_id?: number
  contract_id?: number
  related_id?: number
  notes?: string
  created_at?: string
  updated_at?: string
  company?: { id: number; name: string }
  cashbox?: { id: number; name: string }
  counterparty?: { id: number; name?: string; phone?: string }
  transaction_type?: { id?: number; code?: string; name?: string; sign?: number }
  payment_method?: { id?: number; code?: string; name?: string }
}

export type Receipt = {
  id: number
  sum: Money
  payment_date?: string
  company_id?: number
  cashbox_id?: number
  transaction_id?: number
  contract_id?: number
  counterparty_id?: number
  description?: string
  company?: { id: number; name: string }
  cashbox?: { id: number; name: string }
  counterparty?: { id: number; name?: string; phone?: string }
}

export type Spending = {
  id: number
  sum: Money
  payment_date?: string
  company_id?: number
  cashbox_id?: number
  transaction_id?: number
  fond_id?: number
  spending_item_id?: number
  contract_id?: number
  counterparty_id?: number
  spent_to_user_id?: number
  description?: string
  company?: { id: number; name: string }
  cashbox?: { id: number; name: string }
  fund?: { id: number; name: string }
  item?: { id: number; name: string }
}

export type Contract = {
  id: number
  title?: string
  total_amount?: number
  paid_amount?: number
  system_status_code?: string
  contract_status_id?: number
  counterparty_id?: number
  company_id?: number
  address?: string
  contract_date?: string
  completion_date?: string
  is_completed?: boolean
}

export type CashBox = { id: number; name: string; balance?: number }
export type Company = { id: number; name: string; code?: string }
export type SpendingFund = { id: number; name: string; description?: string }
export type SpendingItem = { id: number; name: string; fond_id?: number }
export type ContractStatus = { id: number; name: string; color?: string }
export type SaleType = { id: number; name: string }
export type City = { id: number; name: string }
export type District = { id: number; name: string; city_id?: number }
