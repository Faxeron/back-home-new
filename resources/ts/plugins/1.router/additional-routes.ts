import type { RouteRecordRaw } from 'vue-router/auto'

const emailRouteComponent = () => import('@/pages/apps/email/index.vue')

type AbilityRule = {
  action?: string
  subject?: string
}

type HomeCandidate = {
  path: string
  action: string
  subject: string
}

const HOME_CANDIDATES: HomeCandidate[] = [
  { path: '/dashboards/crm', action: 'view', subject: 'dashboard.total_sales' },
  { path: '/estimates', action: 'view', subject: 'estimates' },
  { path: '/operations/contracts', action: 'view', subject: 'contracts' },
  { path: '/operations/measurements', action: 'view', subject: 'measurements' },
  { path: '/operations/installations', action: 'view', subject: 'installations' },
  { path: '/products', action: 'view', subject: 'products' },
  { path: '/sales/knowledge', action: 'view', subject: 'knowledge' },
  { path: '/settings/roles-permissions', action: 'view', subject: 'settings.roles' },
]

const hasAbility = (rules: AbilityRule[], action: string, subject: string): boolean => {
  return rules.some(rule => {
    const ruleAction = String(rule.action ?? '').toLowerCase()
    const ruleSubject = String(rule.subject ?? '').toLowerCase()

    if (ruleAction === 'manage' && ruleSubject === 'all')
      return true

    const actionAllowed = ruleAction === action || ruleAction === 'manage'
    const subjectAllowed = ruleSubject === subject || ruleSubject === 'all'

    return actionAllowed && subjectAllowed
  })
}

const resolveHomePath = (rules: AbilityRule[]): string | null => {
  for (const candidate of HOME_CANDIDATES) {
    if (hasAbility(rules, candidate.action, candidate.subject))
      return candidate.path
  }

  return null
}

// Redirects
export const redirects: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'index',
    redirect: to => {
      const accessToken = useCookie<string | null | undefined>('accessToken')
      const userData = useCookie<Record<string, unknown> | null | undefined>('userData')
      const userAbilityRules = useCookie<AbilityRule[] | null | undefined>('userAbilityRules')

      if (accessToken.value)
        accessToken.value = null

      if (!userData.value)
        return { name: 'login', query: to.query }

      const homePath = resolveHomePath(userAbilityRules.value ?? [])
      if (homePath)
        return { path: homePath }

      const userRole = typeof userData.value.role === 'string' ? userData.value.role : ''
      if (userRole === 'client')
        return { name: 'access-control' }

      return { name: 'not-authorized' }
    },
  },
  {
    path: '/pages/user-profile',
    name: 'pages-user-profile',
    redirect: () => ({ name: 'pages-user-profile-tab', params: { tab: 'profile' } }),
  },
  {
    path: '/pages/account-settings',
    name: 'pages-account-settings',
    redirect: () => ({ name: 'pages-account-settings-tab', params: { tab: 'account' } }),
  },
]

export const routes: RouteRecordRaw[] = [
  // Email filter
  {
    path: '/apps/email/filter/:filter',
    name: 'apps-email-filter',
    component: emailRouteComponent,
    meta: {
      navActiveLink: 'apps-email',
      layoutWrapperClasses: 'layout-content-height-fixed',
    },
  },

  // Email label
  {
    path: '/apps/email/label/:label',
    name: 'apps-email-label',
    component: emailRouteComponent,
    meta: {
      // contentClass: 'email-application',
      navActiveLink: 'apps-email',
      layoutWrapperClasses: 'layout-content-height-fixed',
    },
  },

  {
    path: '/dashboards/logistics',
    name: 'dashboards-logistics',
    component: () => import('@/pages/apps/logistics/dashboard.vue'),
  },
  {
    path: '/dashboards/academy',
    name: 'dashboards-academy',
    component: () => import('@/pages/apps/academy/dashboard.vue'),
  },
  {
    path: '/apps/ecommerce/dashboard',
    name: 'apps-ecommerce-dashboard',
    component: () => import('@/pages/dashboards/ecommerce.vue'),
  },
]
