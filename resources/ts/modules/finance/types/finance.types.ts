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
  cashflow_item_id?: number
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
  created_at?: string
  updated_at?: string
  company_id?: number
  cashbox_id?: number
  transaction_id?: number
  cashflow_item_id?: number | null
  contract_id?: number
  counterparty_id?: number
  description?: string
  company?: { id: number; name: string }
  cashbox?: { id: number; name: string }
  counterparty?: { id: number; name?: string; phone?: string }
  contract?: { id: number; counterparty_id?: number }
  creator?: { id: number; name?: string | null; email?: string | null }
}

export type Spending = {
  id: number
  sum: Money
  payment_date?: string
  created_at?: string
  updated_at?: string
  company_id?: number
  cashbox_id?: number
  transaction_id?: number
  fond_id?: number
  spending_item_id?: number
  cashflow_item_id?: number | null
  contract_id?: number
  counterparty_id?: number
  spent_to_user_id?: number
  description?: string
  company?: { id: number; name: string }
  cashbox?: { id: number; name: string }
  counterparty?: { id: number; name?: string; phone?: string }
  fund?: { id: number; name: string }
  item?: { id: number; name: string }
  creator?: { id: number; name?: string | null; email?: string | null }
}

export type Contract = {
  id: number
  title?: string
  total_amount?: number
  paid_amount?: number
  debt?: number
  system_status_code?: string
  contract_status_id?: number
  counterparty_id?: number
  company_id?: number
  address?: string
  contract_date?: string
  city_id?: number | null
  completion_date?: string
  is_completed?: boolean
  work_start_date?: string
  work_end_date?: string
  sale_type_id?: number
  manager_id?: number
  measurer_id?: number
  counterparty?: {
    id: number
    type?: string
    name?: string
    phone?: string
    email?: string
    individual?: {
      first_name?: string
      last_name?: string
      patronymic?: string
      passport_series?: string
      passport_number?: string
      passport_code?: string
      passport_whom?: string
      issued_at?: string
      passport_address?: string
    } | null
    company?: {
      legal_name?: string
      short_name?: string
      inn?: string
      kpp?: string
      ogrn?: string
      legal_address?: string
      postal_address?: string
      director_name?: string
      accountant_name?: string
      bank_name?: string
      bik?: string
      account_number?: string
      correspondent_account?: string
    } | null
  }
  status?: { id: number; name: string; color?: string }
  sale_type?: { id: number; name: string }
  manager?: { id: number; name: string }
  measurer?: { id: number; name: string }
}

export type CashBox = {
  id: number
  name: string
  balance?: number
  logo_url?: string | null
  logo_source?: string | null
  logo_preset_id?: number | null
  company_id?: number
  description?: string
  is_active?: boolean
  company?: { id: number; name: string }
  logo_preset?: { id: number; name: string; logo_url?: string | null }
}
export type CashboxLogo = {
  id: number
  name: string
  file_path?: string
  logo_url?: string | null
}
export type Company = {
  id: number
  name: string
  code?: string
  phone?: string
  email?: string
  address?: string
  is_active?: boolean
}
export type SpendingFund = {
  id: number
  name: string
  description?: string
  is_active?: boolean
  items_count?: number
}
export type SpendingItem = {
  id: number
  name: string
  fond_id?: number
  cashflow_item_id?: number | null
  description?: string
  is_active?: boolean
}

export type CashflowItem = {
  id: number
  parent_id?: number | null
  code: string
  name: string
  section: 'OPERATING' | 'INVESTING' | 'FINANCING'
  direction: 'IN' | 'OUT'
  is_active?: boolean
  sort_order?: number | null
}

export type CashflowReportSummary = {
  date_from: string
  date_to: string
  opening_balance: number
  inflow: number
  outflow: number
  net: number
  closing_balance: number
  currency?: string
}

export type CashflowReportItem = {
  id: number
  code?: string
  name: string
  direction: 'IN' | 'OUT'
  amount_in: number
  amount_out: number
  net: number
}

export type CashflowReportSection = {
  section: 'OPERATING' | 'INVESTING' | 'FINANCING'
  items: CashflowReportItem[]
  totals: { in: number; out: number; net: number }
}

export type CashflowReportTimeline = {
  period: string
  inflow: number
  outflow: number
  net: number
}

export type CashflowReport = {
  summary: CashflowReportSummary
  rows: CashflowReportSection[]
  timeline?: CashflowReportTimeline[] | null
}
export type ContractStatus = {
  id: number
  name: string
  code?: string
  color?: string
  sort_order?: number
  is_active?: boolean
}
export type ContractStatusChange = {
  id: number
  contract_id: number
  previous_status?: { id: number; name: string; color?: string } | null
  new_status?: { id: number; name: string; color?: string } | null
  changed_by?: { id: number; name?: string; email?: string } | null
  changed_at?: string
  created_at?: string
}
export type SaleType = { id: number; name: string; is_active?: boolean }
export type City = { id: number; name: string; is_active?: boolean }
export type District = { id: number; name: string; city_id?: number; is_active?: boolean }
export type TransactionType = {
  id: number
  code?: string
  name?: string
  sign?: number
  is_active?: boolean
  sort_order?: number
}
