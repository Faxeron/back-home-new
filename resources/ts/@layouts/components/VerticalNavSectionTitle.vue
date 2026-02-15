<script lang="ts" setup>
import { layoutConfig } from '@layouts'
import { can } from '@layouts/plugins/casl'
import { useLayoutConfigStore } from '@layouts/stores/config'
import type { NavGroup, NavLink, NavSectionTitle, VerticalNavItems } from '@layouts/types'
import { getDynamicI18nProps } from '@layouts/utils'
import { computed } from 'vue'
import { useCookie } from '@/@core/composable/useCookie'

const props = defineProps<{
  item: NavSectionTitle
  navItems?: VerticalNavItems
  itemIndex?: number
}>()

const configStore = useLayoutConfigStore()
const shallRenderIcon = configStore.isVerticalNavMini()
const userData = useCookie<any>('userData')
const isSuperAdmin = computed(() => userData.value?.role === 'superadmin')

const canViewLink = (item: NavLink): boolean => {
  const allowedByAcl = Boolean(can(item.action, item.subject))
  return allowedByAcl && (!item.superadminOnly || isSuperAdmin.value)
}

const canViewGroup = (item: NavGroup): boolean => {
  const hasVisibleChild = item.children.some(child => 'children' in child ? canViewGroup(child) : canViewLink(child))
  if (!hasVisibleChild)
    return false

  const allowedByAcl = item.action && item.subject ? Boolean(can(item.action, item.subject)) : true
  return allowedByAcl && (!item.superadminOnly || isSuperAdmin.value)
}

const hasVisibleSectionItem = computed(() => {
  if (!Array.isArray(props.navItems) || typeof props.itemIndex !== 'number')
    return true

  for (let index = props.itemIndex + 1; index < props.navItems.length; index++) {
    const item = props.navItems[index]
    if ('heading' in item)
      break

    const isVisible = 'children' in item ? canViewGroup(item) : canViewLink(item)
    if (isVisible)
      return true
  }

  return false
})

const canShow = computed(() => {
  const allowedByAcl = props.item.action && props.item.subject
    ? Boolean(can(props.item.action, props.item.subject))
    : hasVisibleSectionItem.value

  return allowedByAcl && (!props.item.superadminOnly || isSuperAdmin.value)
})
</script>

<template>
  <li
    v-if="canShow"
    class="nav-section-title"
  >
    <div class="title-wrapper">
      <Transition
        name="vertical-nav-section-title"
        mode="out-in"
      >
        <Component
          :is="shallRenderIcon ? layoutConfig.app.iconRenderer : layoutConfig.app.i18n.enable ? 'i18n-t' : 'span'"
          :key="shallRenderIcon"
          :class="shallRenderIcon ? 'placeholder-icon' : 'title-text'"
          v-bind="{ ...layoutConfig.icons.sectionTitlePlaceholder, ...getDynamicI18nProps(item.heading, 'span') }"
        >
          {{ !shallRenderIcon ? item.heading : null }}
        </Component>
      </Transition>
    </div>
  </li>
</template>
