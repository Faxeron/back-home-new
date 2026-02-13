import type { RouteNamedMap, _RouterTyped } from 'unplugin-vue-router'
import { canNavigate } from '@layouts/plugins/casl'
import { useAppSnackbarStore } from '@/stores/appSnackbar'

export const setupGuards = (router: _RouterTyped<RouteNamedMap & { [key: string]: any }>) => {
  // 👉 router.beforeEach
  // Docs: https://router.vuejs.org/guide/advanced/navigation-guards.html#global-before-guards
  router.beforeEach(to => {
    /*
     * If it's a public route, continue navigation. This kind of pages are allowed to visited by login & non-login users. Basically, without any restrictions.
     * Examples of public routes are, 404, under maintenance, etc.
     */
    if (to.meta.public)
      return

    // Cleanup of legacy bearer-token cookie after migration to session cookies.
    const legacyToken = useCookie('accessToken')
    if (legacyToken.value)
      legacyToken.value = null

    // Session auth is now cookie-based (Sanctum stateful), userData is the frontend auth marker.
    const isLoggedIn = !!useCookie('userData').value

    /*
      If user is logged in and is trying to access login like page, redirect to home
      else allow visiting the page
      (WARN: Don't allow executing further by return statement because next code will check for permissions)
     */
    if (to.meta.unauthenticatedOnly) {
      if (isLoggedIn)
        return '/'
      else
        return undefined
    }

    if (!canNavigate(to) && to.matched.length) {
      if (isLoggedIn) {
        useAppSnackbarStore().show('Нет доступа к разделу.', 'warning')
        return false
      }

      return {
        name: 'login',
        query: {
          ...to.query,
          to: to.fullPath !== '/' ? to.path : undefined,
        },
      }
    }
  })
}
