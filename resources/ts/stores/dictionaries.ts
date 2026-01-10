import { defineStore } from 'pinia'
import type { ContractStatus } from '@/types/finance'
import { $api } from '@/utils/api'

interface NamedItem {
  id: number | string
  name: string
  code?: string
  color?: string
  sign?: number | string | null
  fond_id?: number | string | null
}

export const useDictionariesStore = defineStore('dictionaries', {
  state: () => ({
    cashBoxes: [] as NamedItem[],
    companies: [] as NamedItem[],
    spendingFunds: [] as NamedItem[],
    spendingItems: [] as NamedItem[],
    saleTypes: [] as NamedItem[],
    transactionTypes: [] as NamedItem[],
    paymentMethods: [] as NamedItem[],
    counterparties: [] as NamedItem[],
    cities: [] as NamedItem[],
    contractStatuses: [] as ContractStatus[],
    loaded: {
      cashBoxes: false,
      companies: false,
      spendingFunds: false,
      spendingItems: false,
      saleTypes: false,
      transactionTypes: false,
      paymentMethods: false,
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
    async loadSpendingItems() {
      if (this.loaded.spendingItems) return
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
    async loadTransactionTypes() {
      if (this.loaded.transactionTypes) return
      const fallback = [
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
        this.loadSaleTypes(),
        this.loadPaymentMethods(),
        this.loadTransactionTypes(),
        this.loadCounterparties(),
        this.loadCities(),
        this.loadContractStatuses(),
      ])
    },
  },
})
