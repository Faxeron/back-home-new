import { defineStore } from 'pinia'
import { ref } from 'vue'

type SnackbarColor = 'success' | 'error' | 'info' | 'warning'

export const useAppSnackbarStore = defineStore('appSnackbar', () => {
  const open = ref(false)
  const text = ref('')
  const color = ref<SnackbarColor>('success')
  const timeout = ref(2500)
  const location = ref('bottom end')

  const show = (message: string, tone: SnackbarColor = 'success') => {
    text.value = message
    color.value = tone
    open.value = true
  }

  const close = () => {
    open.value = false
  }

  return {
    open,
    text,
    color,
    timeout,
    location,
    show,
    close,
  }
})
