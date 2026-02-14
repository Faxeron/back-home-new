import { defineStore } from 'pinia'
import type { ContractStatus } from '@/types/finance'
import { $api } from '@/utils/api'

interface NamedItem {
  id: number | string
  name: string
  code?: string
  type?: string
  status?: string
  color?: string
  sign?: number | string | null
  fond_id?: number | string | null
  counterparty_id?: number | string | null
  logo_url?: string | null
  section?: string
  direction?: string
  is_active?: boolean
  parent_id?: number | string | null
  sort_order?: number | null
}

export const useDictionariesStore = defineStore('dictionaries', {
  state: () => ({
    cashBoxes: [] as NamedItem[],
    companies: [] as NamedItem[],
    spendingFunds: [] as NamedItem[],
    spendingItems: [] as NamedItem[],
    cashflowItems: [] as NamedItem[],
    saleTypes: [] as NamedItem[],
    transactionTypes: [] as NamedItem[],
    paymentMethods: [] as NamedItem[],
    financeObjects: [] as NamedItem[],
    counterparties: [] as NamedItem[],
    cities: [] as NamedItem[],
    contractStatuses: [] as ContractStatus[],
    loaded: {
      cashBoxes: false,
      companies: false,
      spendingFunds: false,
      spendingItems: false,
      cashflowItems: false,
      saleTypes: false,
      transactionTypes: false,
      paymentMethods: false,
      financeObjects: false,
      counterparties: false,
      cities: false,
      contractStatuses: false,
    },
  }),
  actions: {
    async loadCashBoxes(force = false) {
      if (!force && this.loaded.cashBoxes) return
      try {
        const res: any = await $api('finance/cashboxes')
        const list = Array.isArray(res?.data?.data) ? res.data.data : Array.isArray(res?.data) ? res.data : []
        this.cashBoxes = list
          .map((item: any) => ({
            id: item?.id ?? item?.value,
            name: item?.name ?? item?.cashbox_name ?? item?.title ?? item?.label ?? item?.code,
            logo_url: item?.logo_url ?? null,
          }))
          .filter((item: any) => item.id != null && item.name)
        this.loaded.cashBoxes = true
      } catch (e) {
        console.error('Failed to load cashBoxes', e)
        this.cashBoxes = []
        this.loaded.cashBoxes = true
      }
    },
    async loadCompanies() {
      if (this.loaded.companies) return
      try {
        const res: any = await $api('common/companies')
        const list = Array.isArray(res?.data?.data) ? res.data.data : Array.isArray(res?.data) ? res.data : []
        this.companies = list
          .map((item: any) => ({
            id: item?.id ?? item?.value,
            name: item?.name ?? item?.label,
          }))
          .filter((item: any) => item.id && item.name)
        this.loaded.companies = true
      } catch (e) {
        console.error('Failed to load companies', e)
        this.companies = []
        this.loaded.companies = true
      }
    },
    async loadSpendingFunds() {
      if (this.loaded.spendingFunds) return
      try {
        const res: any = await $api('finance/funds')
        const list = Array.isArray(res?.data?.data) ? res.data.data : Array.isArray(res?.data) ? res.data : []
        this.spendingFunds = list
          .map((item: any) => ({
            id: item?.id ?? item?.value,
            name: item?.name ?? item?.label,
          }))
          .filter((item: any) => item.id && item.name)
        this.loaded.spendingFunds = true
      } catch (e) {
        console.error('Failed to load spending funds', e)
        this.spendingFunds = []
        this.loaded.spendingFunds = true
      }
    },
    async loadSpendingItems(force = false) {
      if (!force && this.loaded.spendingItems) return
      try {
        const res: any = await $api('finance/spending-items')
        const list = Array.isArray(res?.data?.data) ? res.data.data : Array.isArray(res?.data) ? res.data : []
        this.spendingItems = list
          .map((item: any) => ({
            id: item?.id ?? item?.value,
            name: item?.name ?? item?.label,
            fond_id: item?.fond_id ?? item?.fund_id ?? null,
          }))
          .filter((item: any) => item.id && item.name)
        this.loaded.spendingItems = true
      } catch (e) {
        console.error('Failed to load spending items', e)
        this.spendingItems = []
        this.loaded.spendingItems = true
      }
    },
    async loadCashflowItems(force = false) {
      if (!force && this.loaded.cashflowItems) return
      try {
        const res: any = await $api('cashflow-items')
        const list = Array.isArray(res?.data?.data) ? res.data.data : Array.isArray(res?.data) ? res.data : []
        this.cashflowItems = list
          .map((item: any) => ({
            id: item?.id ?? item?.value,
            name: item?.name ?? item?.label,
            code: item?.code ?? undefined,
            section: item?.section ?? undefined,
            direction: item?.direction ?? undefined,
            parent_id: item?.parent_id ?? null,
            sort_order: item?.sort_order ?? null,
            is_active: item?.is_active ?? undefined,
          }))
          .filter((item: any) => item.id && item.name)
        this.loaded.cashflowItems = true
      } catch (e) {
        console.error('Failed to load cashflow items', e)
        this.cashflowItems = []
        this.loaded.cashflowItems = true
      }
    },
    async loadSaleTypes() {
      if (this.loaded.saleTypes) return
      try {
        const res: any = await $api('settings/sale-types')
        const list = Array.isArray(res?.data?.data) ? res.data.data : Array.isArray(res?.data) ? res.data : []
        this.saleTypes = list
          .map((item: any) => ({
            id: item?.id ?? item?.value,
            name: item?.name ?? item?.label,
          }))
          .filter((item: any) => item.id && item.name)
        this.loaded.saleTypes = true
      } catch (e) {
        console.error('Failed to load sale types', e)
        this.saleTypes = []
        this.loaded.saleTypes = true
      }
    },
    async loadPaymentMethods() {
      if (this.loaded.paymentMethods) return
      try {
        const res: any = await $api('finance/payment-methods')
        const list = Array.isArray(res?.data?.data) ? res.data.data : Array.isArray(res?.data) ? res.data : []
        this.paymentMethods = list
          .map((item: any) => ({
            id: item?.id ?? item?.value,
            name: item?.name ?? item?.label ?? item?.code,
            code: item?.code,
          }))
          .filter((item: any) => item.id && item.name)
        this.loaded.paymentMethods = true
      } catch (e) {
        console.error('Failed to load payment methods', e)
        this.paymentMethods = []
        this.loaded.paymentMethods = true
      }
    },
    async loadFinanceObjects(force = false) {
      if (!force && this.loaded.financeObjects) return
      try {
        const res: any = await $api('finance/objects', { params: { per_page: 200, sort: 'updated_at', direction: 'desc' } })
        const list = Array.isArray(res?.data?.data) ? res.data.data : Array.isArray(res?.data) ? res.data : []
        this.financeObjects = list
          .map((item: any) => ({
            id: item?.id ?? item?.value,
            name: item?.name ?? item?.label,
            code: item?.code ?? undefined,
            type: item?.type ?? undefined,
            status: item?.status ?? undefined,
            counterparty_id: item?.counterparty_id ?? null,
          }))
          .filter((item: any) => item.id && item.name)
        this.loaded.financeObjects = true
      } catch (e) {
        console.error('Failed to load finance objects', e)
        this.financeObjects = []
        this.loaded.financeObjects = true
      }
    },
    async loadTransactionTypes() {
      if (this.loaded.transactionTypes) return
      const fallback: NamedItem[] = [
        // Add enum-based items here if API is unavailable
      ]
      try {
        const res: any = await $api('finance/transaction-types')
        const list = Array.isArray(res?.data?.data) ? res.data.data : Array.isArray(res?.data) ? res.data : []
        const mapped = list
          .map((item: any) => ({
            id: item?.id ?? item?.value ?? item?.code,
            name: item?.name ?? item?.label ?? item?.code,
            code: item?.code,
            sign: item?.sign ?? null,
          }))
          .filter((item: any) => item.id && item.name)
        this.transactionTypes = mapped.length ? mapped : fallback
        this.loaded.transactionTypes = true
      } catch (e) {
        console.error('Failed to load transaction types', e)
        this.transactionTypes = fallback
        this.loaded.transactionTypes = true
      }
    },
    async loadCounterparties() {
      if (this.loaded.counterparties) return
      try {
        const res: any = await $api('finance/counterparties')
        const list = Array.isArray(res?.data?.data) ? res.data.data : Array.isArray(res?.data) ? res.data : []
        this.counterparties = list
          .map((item: any) => ({
            id: item?.id ?? item?.value,
            name: item?.name ?? item?.label ?? item?.title,
          }))
          .filter((item: any) => item.id && item.name)
        this.loaded.counterparties = true
      } catch (e) {
        console.error('Failed to load counterparties', e)
        this.counterparties = []
        this.loaded.counterparties = true
      }
    },
    async loadCities(force = false) {
      if (!force && this.loaded.cities) return
      try {
        const res: any = await $api('settings/cities', { params: { per_page: 200 } })
        const list = Array.isArray(res?.data?.data) ? res.data.data : Array.isArray(res?.data) ? res.data : []
        this.cities = list
          .map((item: any) => ({
            id: item?.id ?? item?.value,
            name: item?.name ?? item?.label,
          }))
          .filter((item: any) => item.id != null && item.name)
        this.loaded.cities = true
      } catch (e) {
        console.error('Failed to load cities', e)
        this.cities = []
        this.loaded.cities = true
      }
    },
    async loadContractStatuses(force = false) {
      if (!force && this.loaded.contractStatuses) return
      try {
        const res: any = await $api('settings/contract-statuses', { params: { per_page: 200 } })
        const list = Array.isArray(res?.data?.data) ? res.data.data : Array.isArray(res?.data) ? res.data : []
        this.contractStatuses = list
          .map((item: any) => ({
            id: Number(item?.id ?? item?.value),
            name: item?.name ?? item?.label,
            color: item?.color ?? undefined,
          }))
          .filter((item: any) => Number.isFinite(item.id) && item.name)
        this.loaded.contractStatuses = true
      } catch (e) {
        console.error('Failed to load contract statuses', e)
        this.contractStatuses = []
        this.loaded.contractStatuses = true
      }
    },
    async loadAll() {
      await Promise.all([
        this.loadCashBoxes(),
        this.loadCompanies(),
        this.loadSpendingFunds(),
        this.loadSpendingItems(),
        this.loadCashflowItems(),
        this.loadSaleTypes(),
        this.loadPaymentMethods(),
        this.loadFinanceObjects(),
        this.loadTransactionTypes(),
        this.loadCounterparties(),
        this.loadCities(),
        this.loadContractStatuses(),
      ])
    },
  },
})
