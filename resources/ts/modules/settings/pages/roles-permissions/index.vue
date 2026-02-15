<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { fetchRolesPermissions, updateRolePermissions, updateUserRoles } from '@/modules/settings/api/roles-permissions.api'

definePage({
  meta: {
    action: 'view',
    subject: 'settings.roles',
  },
})

type Role = {
  id: number
  code: string
  name: string
  is_locked?: boolean
}

type Resource = {
  key: string
  label: string
}

type Action = {
  key: string
  label: string
}

type ScopeOption = {
  key: string
  label: string
}

type UserRow = {
  id: number
  name?: string | null
  email?: string | null
  role_ids: number[]
}

const loading = ref(false)
const savingRoleId = ref<number | null>(null)
const savingUserId = ref<number | null>(null)
const errorMessage = ref('')

const tab = ref<'roles' | 'users'>('roles')
const selectedRoleId = ref<number | null>(null)

const roles = ref<Role[]>([])
const resources = ref<Resource[]>([])
const actions = ref<Action[]>([])
const scopeOptions = ref<ScopeOption[]>([])
const rolePermissions = ref<Record<number, string[]>>({})
const roleScopes = ref<Record<number, Record<string, string>>>({})
const users = ref<UserRow[]>([])
const DASHBOARD_RESOURCE_PREFIX = 'dashboard.'

const userSearch = ref('')

const filteredUsers = computed(() => {
  const query = userSearch.value.trim().toLowerCase()
  if (!query)
    return users.value
  return users.value.filter(user =>
    `${user.name ?? ''} ${user.email ?? ''}`.toLowerCase().includes(query),
  )
})

const roleOptions = computed(() =>
  roles.value.map(role => ({
    title: role.name,
    value: role.id,
  })),
)

const activeRole = computed(() => roles.value.find(role => role.id === selectedRoleId.value))
const gridTemplate = computed(() =>
  `minmax(180px, 1.2fr) repeat(${actions.value.length || 1}, minmax(80px, 1fr)) minmax(140px, 0.9fr)`,
)
const mainResources = computed(() =>
  resources.value.filter(resource => !resource.key.startsWith(DASHBOARD_RESOURCE_PREFIX)),
)
const dashboardResources = computed(() =>
  resources.value.filter(resource => resource.key.startsWith(DASHBOARD_RESOURCE_PREFIX)),
)

const loadData = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const response = await fetchRolesPermissions()
    roles.value = response?.roles ?? []
    resources.value = response?.resources ?? []
    actions.value = response?.actions ?? []
    scopeOptions.value = response?.scope_options ?? []
    rolePermissions.value = response?.role_permissions ?? {}
    roleScopes.value = response?.role_scopes ?? {}
    users.value = response?.users ?? []

    if (!selectedRoleId.value && roles.value.length)
      selectedRoleId.value = roles.value[0].id
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось загрузить права и роли.'
  } finally {
    loading.value = false
  }
}

const permissionKey = (resource: string, action: string) => `${resource}.${action}`

const isPermissionChecked = (roleId: number, resource: string, action: string) => {
  const list = rolePermissions.value[roleId] ?? []
  return list.includes(permissionKey(resource, action))
}

const togglePermission = (roleId: number, resource: string, action: string, checked: boolean | null) => {
  const key = permissionKey(resource, action)
  const list = new Set(rolePermissions.value[roleId] ?? [])
  if (checked === true) list.add(key)
  else list.delete(key)
  rolePermissions.value[roleId] = Array.from(list)
}

const getScopeValue = (roleId: number, resource: string) =>
  roleScopes.value?.[roleId]?.[resource] ?? 'company'

const setScopeValue = (roleId: number, resource: string, scope: string) => {
  if (!roleScopes.value[roleId]) roleScopes.value[roleId] = {}
  roleScopes.value[roleId][resource] = scope
}

const saveRole = async () => {
  if (!selectedRoleId.value) return
  const roleId = selectedRoleId.value
  const role = roles.value.find(item => item.id === roleId)
  if (!role || role.is_locked) return

  savingRoleId.value = roleId
  try {
    await updateRolePermissions(roleId, {
      permissions: rolePermissions.value[roleId] ?? [],
      scopes: roleScopes.value[roleId] ?? {},
    })
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось сохранить права.'
  } finally {
    savingRoleId.value = null
  }
}

const saveUserRoles = async (user: UserRow) => {
  savingUserId.value = user.id
  try {
    await updateUserRoles(user.id, user.role_ids ?? [])
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Не удалось сохранить роли пользователя.'
  } finally {
    savingUserId.value = null
  }
}

onMounted(loadData)
</script>

<template>
  <div class="d-flex flex-column gap-4">
    <div class="d-flex flex-wrap align-center justify-space-between gap-3">
      <div>
        <h2 class="text-h5 mb-1">Права и роли</h2>
        <div class="text-sm text-muted">Управление доступом к страницам и действиям.</div>
      </div>
    </div>

    <VAlert v-if="errorMessage" type="error" variant="tonal">
      {{ errorMessage }}
    </VAlert>

    <VTabs v-model="tab" color="primary">
      <VTab value="roles">Роли</VTab>
      <VTab value="users">Пользователи</VTab>
    </VTabs>

    <VWindow v-model="tab">
      <VWindowItem value="roles">
        <VCard>
          <VCardText class="d-flex flex-column gap-4">
            <div class="d-flex flex-wrap gap-2">
              <VChip
                v-for="role in roles"
                :key="role.id"
                :color="selectedRoleId === role.id ? 'primary' : 'default'"
                @click="selectedRoleId = role.id"
              >
                {{ role.name }}
              </VChip>
            </div>

            <VProgressLinear v-if="loading" indeterminate color="primary" />

            <div v-if="activeRole?.is_locked" class="text-sm text-muted">
              Для роли {{ activeRole?.name }} включен полный доступ. Изменения недоступны.
            </div>

            <div class="permissions-grid">
              <div class="permissions-row permissions-header" :style="{ gridTemplateColumns: gridTemplate }">
                <div class="permissions-cell resource-cell">Раздел</div>
                <div
                  v-for="action in actions"
                  :key="action.key"
                  class="permissions-cell action-cell"
                >
                  {{ action.label }}
                </div>
                <div class="permissions-cell scope-cell">Доступ</div>
              </div>

              <div
                v-for="resource in mainResources"
                :key="resource.key"
                class="permissions-row"
                :style="{ gridTemplateColumns: gridTemplate }"
              >
                <div class="permissions-cell resource-cell">
                  {{ resource.label }}
                </div>
                <div
                  v-for="action in actions"
                  :key="`${resource.key}-${action.key}`"
                  class="permissions-cell action-cell"
                >
                  <VCheckbox
                    :model-value="selectedRoleId ? isPermissionChecked(selectedRoleId, resource.key, action.key) : false"
                    :disabled="!selectedRoleId || activeRole?.is_locked"
                    hide-details
                    @update:modelValue="value => {
                      if (selectedRoleId)
                        togglePermission(selectedRoleId, resource.key, action.key, value)
                    }"
                  />
                </div>
                <div class="permissions-cell scope-cell">
                  <VSelect
                    :model-value="selectedRoleId ? getScopeValue(selectedRoleId, resource.key) : 'company'"
                    :items="scopeOptions"
                    item-title="label"
                    item-value="key"
                    hide-details
                    density="compact"
                    :disabled="!selectedRoleId || activeRole?.is_locked"
                    @update:modelValue="value => {
                      if (selectedRoleId && value)
                        setScopeValue(selectedRoleId, resource.key, String(value))
                    }"
                  />
                </div>
              </div>
            </div>

            <div class="dashboard-permissions d-flex flex-column gap-2">
              <div class="text-subtitle-2 font-weight-semibold">
                Модули дашборда
              </div>
              <div class="text-sm text-medium-emphasis">
                Включайте видимость отдельных модулей на главном дашборде.
              </div>

              <div class="dashboard-grid">
                <div class="dashboard-row dashboard-header">
                  <div>Модуль</div>
                  <div>Доступ</div>
                </div>

                <div v-if="!dashboardResources.length" class="dashboard-empty">
                  Нет доступных модулей дашборда.
                </div>

                <div
                  v-for="resource in dashboardResources"
                  :key="`dashboard-${resource.key}`"
                  class="dashboard-row"
                >
                  <div class="resource-cell">
                    {{ resource.label }}
                  </div>
                  <div class="action-cell">
                    <VCheckbox
                      :model-value="selectedRoleId ? isPermissionChecked(selectedRoleId, resource.key, 'view') : false"
                      :disabled="!selectedRoleId || activeRole?.is_locked"
                      hide-details
                      @update:modelValue="value => {
                        if (selectedRoleId)
                          togglePermission(selectedRoleId, resource.key, 'view', value)
                      }"
                    />
                  </div>
                </div>
              </div>
            </div>

            <div class="d-flex justify-end">
              <VBtn
                color="primary"
                :loading="savingRoleId === selectedRoleId"
                :disabled="!selectedRoleId || activeRole?.is_locked"
                @click="saveRole"
              >
                Сохранить
              </VBtn>
            </div>
          </VCardText>
        </VCard>
      </VWindowItem>

      <VWindowItem value="users">
        <VCard>
          <VCardText class="d-flex flex-column gap-4">
            <VTextField
              v-model="userSearch"
              label="Поиск пользователя"
              prepend-inner-icon="tabler-search"
              hide-details
              clearable
            />

            <VProgressLinear v-if="loading" indeterminate color="primary" />

            <div class="users-table">
              <div class="users-row users-header">
                <div>Пользователь</div>
                <div>Роли</div>
                <div></div>
              </div>
              <div
                v-for="user in filteredUsers"
                :key="user.id"
                class="users-row"
              >
                <div>
                  <div class="text-subtitle-2 font-weight-semibold">{{ user.name || 'Без имени' }}</div>
                  <div class="text-xs text-muted">{{ user.email }}</div>
                </div>
                <div>
                  <VSelect
                    v-model="user.role_ids"
                    :items="roleOptions"
                    multiple
                    chips
                    hide-details
                    density="compact"
                  />
                </div>
                <div class="d-flex justify-end">
                  <VBtn
                    variant="text"
                    :loading="savingUserId === user.id"
                    @click="saveUserRoles(user)"
                  >
                    Сохранить
                  </VBtn>
                </div>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VWindowItem>
    </VWindow>
  </div>
</template>

<style scoped>
.permissions-grid {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 12px;
  overflow: hidden;
}

.permissions-row {
  display: grid;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.06);
  align-items: center;
}

.permissions-row:last-child {
  border-bottom: none;
}

.permissions-header {
  background: rgba(var(--v-theme-on-surface), 0.03);
  font-weight: 600;
}

.permissions-cell {
  padding: 10px 12px;
}

.resource-cell {
  font-weight: 600;
}

.action-cell {
  display: flex;
  justify-content: center;
}

.scope-cell {
  min-width: 140px;
}

.dashboard-grid {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 12px;
  overflow: hidden;
}

.dashboard-row {
  display: grid;
  grid-template-columns: minmax(220px, 1fr) minmax(120px, 0.45fr);
  align-items: center;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.06);
}

.dashboard-row:last-child {
  border-bottom: none;
}

.dashboard-header {
  background: rgba(var(--v-theme-on-surface), 0.03);
  font-weight: 600;
}

.dashboard-empty {
  padding: 12px;
  font-size: 0.875rem;
  color: rgba(var(--v-theme-on-surface), 0.6);
}

.users-table {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 12px;
  overflow: hidden;
}

.users-row {
  display: grid;
  grid-template-columns: minmax(200px, 1fr) minmax(220px, 2fr) 120px;
  gap: 16px;
  padding: 12px 16px;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.06);
  align-items: center;
}

.users-row:last-child {
  border-bottom: none;
}

.users-header {
  background: rgba(var(--v-theme-on-surface), 0.03);
  font-weight: 600;
}
</style>
