import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'

type ScrollHeightOptions = {
  minHeight?: number
  padding?: number
  defaultHeight?: string
}

export const useTableScrollHeight = (
  tableRef: { value: any },
  options: ScrollHeightOptions = {},
) => {
  const {
    minHeight = 320,
    padding = 24,
    defaultHeight = '700px',
  } = options

  const scrollHeight = ref(defaultHeight)

  const updateScrollHeight = () => {
    const tableEl = tableRef.value?.$el as HTMLElement | undefined
    if (!tableEl) return
    const rect = tableEl.getBoundingClientRect()
    const nextHeight = Math.max(minHeight, window.innerHeight - rect.top - padding)
    scrollHeight.value = `${Math.floor(nextHeight)}px`
  }

  const handleResize = () => {
    updateScrollHeight()
  }

  onMounted(async () => {
    await nextTick()
    updateScrollHeight()
    window.addEventListener('resize', handleResize)
  })

  onBeforeUnmount(() => {
    window.removeEventListener('resize', handleResize)
  })

  return {
    scrollHeight,
    updateScrollHeight,
  }
}
