<script lang="ts" setup>
import { layoutConfig } from '@layouts'
import { can } from '@layouts/plugins/casl'
import { useLayoutConfigStore } from '@layouts/stores/config'
import type { NavSectionTitle } from '@layouts/types'
import { getDynamicI18nProps } from '@layouts/utils'
import { computed } from 'vue'
import { useCookie } from '@/@core/composable/useCookie'

const props = defineProps<{
  item: NavSectionTitle
}>()

const configStore = useLayoutConfigStore()
const shallRenderIcon = configStore.isVerticalNavMini()
const userData = useCookie<any>('userData')
const isSuperAdmin = computed(() => userData.value?.role === 'superadmin')
const canShow = computed(() => can(props.item.action, props.item.subject) && (!props.item.superadminOnly || isSuperAdmin.value))
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
