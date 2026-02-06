import type { NavGroup, NavLink, NavSectionTitle, VerticalNavItems } from '@layouts/types'
import { resolveResourceByPath } from '@/modules/acl/acl.config'

const resolvePath = (item: NavLink): string | null => {
  if (!item.to) return null
  if (typeof item.to === 'string') return item.to.startsWith('/') ? item.to : null
  if (typeof item.to === 'object' && 'path' in item.to) return item.to.path ?? null
  return null
}

export const applyNavPermissions = (items: VerticalNavItems): VerticalNavItems => {
  return items.map(item => {
    if ('heading' in item) return item as NavSectionTitle
    if ('children' in item) {
      const group = item as NavGroup
      return {
        ...group,
        children: applyNavPermissions(group.children as VerticalNavItems) as NavGroup['children'],
      }
    }

    const link = item as NavLink
    const path = resolvePath(link)
    const resource = path ? resolveResourceByPath(path) : null
    if (!resource) return link

    return {
      ...link,
      action: link.action ?? 'view',
      subject: link.subject ?? resource,
    }
  })
}
