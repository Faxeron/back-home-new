export type AclResourcePath = {
  resource: string
  paths: string[]
}

export const ACL_RESOURCE_PATHS: AclResourcePath[] = [
  { resource: 'contract_templates', paths: ['/operations/contracts/templates'] },
  { resource: 'contracts', paths: ['/operations/contracts'] },
  { resource: 'dashboard.total_sales', paths: ['/dashboards/crm', '/dashboards/all'] },
  { resource: 'dashboard.employee', paths: ['/dashboards/employee'] },
  { resource: 'estimate_templates', paths: ['/estimate-templates'] },
  { resource: 'estimates', paths: ['/estimates'] },
  { resource: 'measurements', paths: ['/operations/measurements'] },
  { resource: 'installations', paths: ['/operations/installations'] },
  { resource: 'pricebook', paths: ['/products/price'] },
  { resource: 'products', paths: ['/products'] },
  { resource: 'finance', paths: ['/finance', '/finances', '/operations/finance-objects', '/dashboards/new'] },
  { resource: 'knowledge', paths: ['/sales/knowledge'] },
  { resource: 'settings.roles', paths: ['/settings/roles-permissions'] },
  { resource: 'settings.cash_boxes', paths: ['/settings/cash-boxes'] },
  { resource: 'settings.companies', paths: ['/settings/companies'] },
  { resource: 'settings.spending_funds', paths: ['/settings/spending-funds'] },
  { resource: 'settings.spending_items', paths: ['/settings/spending-items'] },
  { resource: 'settings.cashflow_items', paths: ['/settings/cashflow-items'] },
  { resource: 'settings.contract_statuses', paths: ['/settings/contract-statuses'] },
  { resource: 'settings.transaction_types', paths: ['/settings/transaction-types'] },
  { resource: 'settings.sale_types', paths: ['/settings/sale-types'] },
  { resource: 'settings.cities', paths: ['/settings/cities'] },
  { resource: 'settings.districts', paths: ['/settings/districts'] },
  { resource: 'settings.payroll', paths: ['/settings/payroll'] },
  { resource: 'settings.margin', paths: ['/settings/margin'] },
  { resource: 'dev_control', paths: ['/dev-control'] },
]

export const resolveResourceByPath = (path: string): string | null => {
  if (!path) return null
  const normalized = path.toLowerCase()
  for (const entry of ACL_RESOURCE_PATHS) {
    for (const prefix of entry.paths) {
      if (normalized.startsWith(prefix.toLowerCase())) {
        return entry.resource
      }
    }
  }
  return null
}
