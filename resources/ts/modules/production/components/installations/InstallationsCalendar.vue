<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import listPlugin from '@fullcalendar/list'
import timeGridPlugin from '@fullcalendar/timegrid'
import ruLocale from '@fullcalendar/core/locales/ru'
import type { CalendarApi, CalendarOptions } from '@fullcalendar/core'
import type { InstallationRow } from '../../types/installations.types'

const props = defineProps<{
  rows: InstallationRow[]
  loading?: boolean
}>()

const emit = defineEmits<{
  (e: 'toggle-sidebar'): void
}>()

const router = useRouter()
const refCalendar = ref<null | { getApi: () => CalendarApi }>(null)

const addDays = (value: string, days = 1) => {
  const date = new Date(value)
  date.setDate(date.getDate() + days)
  return date.toISOString().split('T')[0]
}

const statusColor = (status: InstallationRow['status']) => {
  if (status === 'completed') return 'success'
  if (status === 'assigned') return 'warning'
  return 'secondary'
}

const events = computed(() =>
  props.rows
    .map(row => {
      const hasActual = Boolean(row.work_done_date && row.worker_id)
      const start = hasActual
        ? row.work_done_date
        : row.work_start_date || row.work_done_date

      if (!start) return null

      const end = hasActual
        ? addDays(start, 1)
        : row.work_end_date
          ? addDays(row.work_end_date, 1)
          : addDays(start, 1)

      const titleParts = [
        row.contract_id ? `Договор #${row.contract_id}` : null,
        row.counterparty_name,
      ].filter(Boolean)

      return {
        id: String(row.contract_id),
        title: titleParts.join(' • '),
        start,
        end,
        allDay: true,
        extendedProps: {
          contractId: row.contract_id,
          status: row.status,
        },
      }
    })
    .filter(Boolean),
)

const calendarOptions = computed<CalendarOptions>(() => ({
  plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  locale: ruLocale,
  height: 'auto',
  headerToolbar: {
    start: 'drawerToggler,prev,next title',
    end: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth',
  },
  customButtons: {
    drawerToggler: {
      text: 'calendarDrawerToggler',
      click() {
        emit('toggle-sidebar')
      },
    },
  },
  dayMaxEvents: 2,
  navLinks: true,
  eventClassNames({ event }) {
    const status = event.extendedProps?.status as InstallationRow['status'] | undefined
    const color = statusColor(status)
    return [`bg-light-${color} text-${color}`]
  },
  eventClick: info => {
    const contractId = info.event.extendedProps?.contractId
    if (contractId) {
      router.push(`/operations/contracts/${contractId}`)
    }
  },
  events: events.value as any,
}))

const jumpToDate = (value: string) => {
  if (!value) return
  const api = refCalendar.value?.getApi()
  api?.gotoDate(new Date(value))
}

defineExpose({ jumpToDate })
</script>

<template>
  <FullCalendar
    ref="refCalendar"
    :options="calendarOptions"
  />
</template>

<style lang="scss">
@use "@core-scss/template/libs/full-calendar";
</style>
