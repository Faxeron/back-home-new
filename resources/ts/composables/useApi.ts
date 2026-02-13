import { createFetch } from '@vueuse/core'
import { destr } from 'destr'

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

export const useApi = createFetch({
  baseUrl: import.meta.env.VITE_API_BASE_URL || '/api',
  fetchOptions: {
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
  },
  options: {
    refetch: true,
    async beforeFetch({ options }) {
      options.credentials = 'include'

      if (useCookie('accessToken').value)
        useCookie('accessToken').value = null

      if (isUnsafeMethod(options.method as string | undefined)) {
        const csrfToken = getCookieValue('XSRF-TOKEN')
        if (csrfToken) {
          options.headers = {
            ...options.headers,
            'X-XSRF-TOKEN': csrfToken,
          }
        }
      }

      return { options }
    },
    afterFetch(ctx) {
      const { data, response } = ctx

      // Parse data if it's JSON

      let parsedData = null
      try {
        parsedData = destr(data)
      }
      catch (error) {
        console.error(error)
      }

      return { data: parsedData, response }
    },
    onFetchError(ctx) {
      if (ctx.response?.status === 401 && typeof window !== 'undefined') {
        clearAuthState()
        if (!window.location.pathname.startsWith('/login')) {
          const to = encodeURIComponent(`${window.location.pathname}${window.location.search}`)
          window.location.assign(`/login?to=${to}`)
        }
      }

      return ctx
    },
  },
})
