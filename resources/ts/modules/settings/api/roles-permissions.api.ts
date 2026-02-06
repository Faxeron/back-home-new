import { $api } from '@/utils/api'

export type RolePermissionPayload = {
  permissions: string[]
  scopes: Record<string, string>
}

export const fetchRolesPermissions = async () => {
  const response = await $api('/settings/roles-permissions')
  return response?.data ?? response
}

export const updateRolePermissions = async (roleId: number, payload: RolePermissionPayload) => {
  await $api(`/settings/roles-permissions/roles/${roleId}`, {
    method: 'PATCH',
    body: payload,
  })
}

export const updateUserRoles = async (userId: number, roleIds: number[]) => {
  await $api(`/settings/roles-permissions/users/${userId}`, {
    method: 'PATCH',
    body: { role_ids: roleIds },
  })
}
