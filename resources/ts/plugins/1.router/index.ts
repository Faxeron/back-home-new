import { setupLayouts } from 'virtual:meta-layouts'
import type { App } from 'vue'

import type { RouteRecordRaw } from 'vue-router/auto'

import { createRouter, createWebHistory } from 'vue-router/auto'

import { redirects, routes } from './additional-routes'
import { setupGuards } from './guards'

const DYNAMIC_IMPORT_ERROR_RETRY_KEY = 'erp_dynamic_import_retry'

const isDynamicImportError = (error: unknown): boolean => {
  const message = error instanceof Error ? error.message : String(error ?? '')
  const normalized = message.toLowerCase()

  return normalized.includes('failed to fetch dynamically imported module')
    || normalized.includes('importing a module script failed')
    || normalized.includes('error loading dynamically imported module')
    || normalized.includes('/@id/virtual:')
}

function recursiveLayouts(route: RouteRecordRaw): RouteRecordRaw {
  if (route.children) {
    for (let i = 0; i < route.children.length; i++)
      route.children[i] = recursiveLayouts(route.children[i])

    return route
  }

  return setupLayouts([route])[0]
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to) {
    if (to.hash)
      return { el: to.hash, behavior: 'smooth', top: 60 }

    return { top: 0 }
  },
  extendRoutes: pages => [
    ...redirects,
    ...[
      ...pages,
      ...routes,
    ].map(route => recursiveLayouts(route)),
  ],
})

setupGuards(router)

router.onError(error => {
  if (typeof window === 'undefined' || !isDynamicImportError(error))
    return

  // Vite dev can temporarily return 404 for virtual/lazy modules; one hard reload restores graph.
  const alreadyRetried = window.sessionStorage.getItem(DYNAMIC_IMPORT_ERROR_RETRY_KEY) === '1'

  if (alreadyRetried) {
    window.sessionStorage.removeItem(DYNAMIC_IMPORT_ERROR_RETRY_KEY)

    return
  }

  window.sessionStorage.setItem(DYNAMIC_IMPORT_ERROR_RETRY_KEY, '1')
  window.location.reload()
})

router.afterEach(() => {
  if (typeof window === 'undefined')
    return

  window.sessionStorage.removeItem(DYNAMIC_IMPORT_ERROR_RETRY_KEY)
})

export { router }

export default function (app: App) {
  app.use(router)
}
