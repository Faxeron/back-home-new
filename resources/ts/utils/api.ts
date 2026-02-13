import { ofetch } from 'ofetch'

const getCookieValue = (name: string): string | null => {
  if (typeof document === 'undefined') return null
  const escaped = name.replace(/[-[\]/{}()*+?.\\^$|]/g, '\\$&')
  const match = document.cookie.match(new RegExp(`(?:^|; )${escaped}=([^;]*)`))
  return match ? decodeURIComponent(match[1]) : null
}

const isUnsafeMethod = (method?: string) => {
  const normalized = String(method || 'GET').toUpperCase()
  return !['GET', 'HEAD', 'OPTIONS'].includes(normalized)
}

const clearAuthState = () => {
  useCookie('accessToken').value = null
  useCookie('userData').value = null
  useCookie('userAbilityRules').value = null
}

export const $api = ofetch.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  credentials: 'include',
  async onRequest({ options }) {
    options.credentials = 'include'

    // Clean up legacy bearer-token auth cookie if it still exists.
    if (useCookie('accessToken').value)
      useCookie('accessToken').value = null

    const headers = new Headers(options.headers as HeadersInit)
    headers.set('Accept', 'application/json')
    headers.set('X-Requested-With', 'XMLHttpRequest')

    if (isUnsafeMethod(options.method as string | undefined)) {
      const csrfToken = getCookieValue('XSRF-TOKEN')
      if (csrfToken)
        headers.set('X-XSRF-TOKEN', csrfToken)
    }

    options.headers = headers
  },
  async onResponseError({ response }) {
    if (response.status !== 401 || typeof window === 'undefined') return

    clearAuthState()

    if (window.location.pathname.startsWith('/login')) return

    const to = encodeURIComponent(`${window.location.pathname}${window.location.search}`)
    window.location.assign(`/login?to=${to}`)
  },
})
