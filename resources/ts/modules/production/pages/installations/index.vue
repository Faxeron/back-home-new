<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useCookie } from '@/@core/composable/useCookie'
import { $api } from '@/utils/api'
import { useTableScrollHeight } from '@/composables/useTableScrollHeight'
import { useInstallations } from '@/modules/production/composables/useInstallations'
import InstallationsList from '@/modules/production/components/installations/InstallationsList.vue'
import InstallationsCalendar from '@/modules/production/components/installations/InstallationsCalendar.vue'
import InstallationAssignDialog from '@/modules/production/components/installations/InstallationAssignDialog.vue'
import type { InstallationRow } from '@/modules/production/types/installations.types'

type UserOption = { id: number; name?: string | null; email?: string | null }

type StatusOption = { label: string; value: 'waiting' | 'assigned' | 'completed' }

const tabs = ref<'calendar' | 'list'>('calendar')
const assignDialog = ref(false)
const selectedRow = ref<InstallationRow | null>(null)
const users = ref<UserOption[]>([])
const usersLoading = ref(false)
const saving = ref(false)
const tableRef = ref<any>(null)
const calendarRef = ref<any>(null)

const { isLeftSidebarOpen } = useResponsiveLeftSidebar()

const userData = useCookie<any>('userData')
const currentUserId = computed(() => Number(userData.value?.id ?? 0) || null)

const {
  rows,
  total,
  loading,
  error,
  statusFilter,
  workerFilter,
  virtualScrollerOptions,
  load,
  assign,
} = useInstallations()

const { scrollHeight } = useTableScrollHeight(tableRef)

const statusOptions: StatusOption[] = [
  { label: 'Ожидание', value: 'waiting' },
  { label: 'Назначен', value: 'assigned' },
  { label: 'Выполнен', value: 'completed' },
]

const workerOptions = computed(() => [
  { title: 'Все монтажники', value: 'all' },
  ...users.value.map(user => ({
    title: user.name || user.email || `#${user.id}`,
    value: user.id,
  })),
])

const showCalendarSidebar = computed(() => tabs.value === 'calendar')
const showTopFilters = computed(() => tabs.value === 'list')

const loadUsers = async () => {
  usersLoading.value = true
  try {
    const response = await $api('/settings/users')
    users.value = response?.data ?? []
  } catch (err) {
    users.value = []
  } finally {
    usersLoading.value = false
  }
}

const openAssign = (row: InstallationRow) => {
  selectedRow.value = row
  assignDialog.value = true
}

const handleAssign = async (payload: { contractId: number; work_done_date: string; worker_id: number }) => {
  saving.value = true
  try {
    await assign(payload.contractId, {
      work_done_date: payload.work_done_date,
      worker_id: payload.worker_id,
    })
    assignDialog.value = false
    await load()
  } finally {
    saving.value = false
  }
}

const jumpToDate = (value: string) => {
  if (!value) return
  if (calendarRef.value?.jumpToDate) {
    calendarRef.value.jumpToDate(value)
  }
}

watch([statusFilter, workerFilter], () => {
  load()
}, { deep: true })

onMounted(async () => {
  await load()
  await loadUsers()
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardTitle>Монтажи</VCardTitle>
        <VDivider />
        <VCardText>
          <div v-if="showTopFilters" class="d-flex flex-wrap align-center gap-3 mb-4">
            <AppSelect
              v-model="workerFilter"
              label="Монтажник"
              :items="workerOptions"
              item-title="title"
              item-value="value"
              class="installations-filter"
              :loading="usersLoading"
            />
            <div class="d-flex flex-wrap align-center gap-3">
              <VCheckbox
                v-for="status in statusOptions"
                :key="status.value"
                v-model="statusFilter"
                :value="status.value"
                density="compact"
                hide-details
              >
                <template #label>
                  <span class="status-label">
                    <span class="status-dot" :class="`status-dot--${status.value}`" />
                    {{ status.label }}
                  </span>
                </template>
              </VCheckbox>
            </div>
            <VBtn
              icon="tabler-refresh"
              variant="text"
              :loading="loading"
              @click="load"
            />
            <span v-if="error" class="text-sm" style="color:#b91c1c">{{ error }}</span>
          </div>

          <VTabs v-model="tabs" class="mb-4">
            <VTab value="calendar">Календарь</VTab>
            <VTab value="list">Список</VTab>
          </VTabs>

          <VWindow v-model="tabs">
            <VWindowItem value="calendar">
              <VLayout class="installations-calendar-layout" style="z-index: 0;">
                <VNavigationDrawer
                  v-if="showCalendarSidebar"
                  v-model="isLeftSidebarOpen"
                  data-allow-mismatch
                  width="292"
                  absolute
                  touchless
                  location="start"
                  class="calendar-add-event-drawer"
                  :temporary="$vuetify.display.mdAndDown"
                >
                  <div style="margin: 1.5rem;">
                    <VBtn
                      block
                      prepend-icon="tabler-plus"
                    >
                      Добавить монтаж
                    </VBtn>
                  </div>

                  <VDivider />

                  <div class="d-flex align-center justify-center pa-2">
                    <AppDateTimePicker
                      id="installations-date-picker"
                      :model-value="new Date().toJSON().slice(0, 10)"
                      :config="{ inline: true }"
                      class="calendar-date-picker"
                      @update:model-value="jumpToDate"
                    />
                  </div>

                  <VDivider />

                  <div class="pa-6">
                    <AppSelect
                      v-model="workerFilter"
                      label="Монтажник"
                      :items="workerOptions"
                      item-title="title"
                      item-value="value"
                      class="installations-filter"
                      :loading="usersLoading"
                    />
                    <div class="d-flex flex-column gap-2 mt-4">
                      <VCheckbox
                        v-for="status in statusOptions"
                        :key="status.value"
                        v-model="statusFilter"
                        :value="status.value"
                        density="compact"
                        hide-details
                      >
                        <template #label>
                          <span class="status-label">
                            <span class="status-dot" :class="`status-dot--${status.value}`" />
                            {{ status.label }}
                          </span>
                        </template>
                      </VCheckbox>
                    </div>
                    <div class="d-flex justify-end mt-2">
                      <VBtn
                        icon="tabler-refresh"
                        variant="text"
                        :loading="loading"
                        @click="load"
                      />
                    </div>
                    <span v-if="error" class="text-sm" style="color:#b91c1c">{{ error }}</span>
                  </div>
                </VNavigationDrawer>

                <VMain>
                  <VCard flat>
                    <InstallationsCalendar
                      ref="calendarRef"
                      :rows="rows"
                      :loading="loading || usersLoading"
                      @toggle-sidebar="isLeftSidebarOpen = true"
                    />
                  </VCard>
                </VMain>
              </VLayout>
            </VWindowItem>
            <VWindowItem value="list">
              <InstallationsList
                ref="tableRef"
                :rows="rows"
                :loading="loading"
                :totalRecords="total"
                :scrollHeight="scrollHeight"
                :virtualScrollerOptions="virtualScrollerOptions"
                @assign="openAssign"
              />
            </VWindowItem>
          </VWindow>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <InstallationAssignDialog
    v-model="assignDialog"
    :row="selectedRow"
    :users="users"
    :loading="saving || usersLoading"
    :current-user-id="currentUserId"
    @save="handleAssign"
  />
</template>

<style lang="scss">
@use "@core-scss/template/libs/full-calendar";

.calendar-add-event-drawer {
  &.v-navigation-drawer:not(.v-navigation-drawer--temporary) {
    border-end-start-radius: 0.375rem;
    border-start-start-radius: 0.375rem;
  }

  &.v-navigation-drawer--temporary:not(.v-navigation-drawer--active) {
    transform: translateX(-110%) !important;
  }
}

.calendar-date-picker {
  display: none;

  +.flatpickr-input {
    +.flatpickr-calendar.inline {
      border: none;
      box-shadow: none;

      .flatpickr-months {
        border-block-end: none;
      }
    }
  }

  & ~ .flatpickr-calendar .flatpickr-weekdays {
    margin-block: 0 4px;
  }
}

@media screen and (max-width: 1279px) {
  .calendar-add-event-drawer {
    border-width: 0;
  }
}
</style>

<style scoped>
.installations-filter {
  min-width: 220px;
}

.status-label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  display: inline-block;
}

.status-dot--waiting {
  background: #64748b;
}

.status-dot--assigned {
  background: #f59e0b;
}

.status-dot--completed {
  background: #22c55e;
}

.installations-calendar-layout {
  overflow: visible !important;
}

.installations-calendar-layout .v-card {
  overflow: visible;
}
</style>
