<script setup lang="ts">
import { defineAsyncComponent, ref } from 'vue'

definePage({
  meta: {
    action: 'view',
    subject: 'dashboard.total_sales',
  },
})

type DashboardEntry = {
  key: string
  title: string
  path: string
  component: ReturnType<typeof defineAsyncComponent>
}

const showEmbedded = ref(true)

const dashboards: DashboardEntry[] = [
  {
    key: 'analytics',
    title: 'Analytics',
    path: '/dashboards/analytics',
    component: defineAsyncComponent(() => import('@/pages/dashboards/analytics.vue')),
  },
  {
    key: 'crm',
    title: 'CRM',
    path: '/dashboards/crm',
    component: defineAsyncComponent(() => import('@/pages/dashboards/crm.vue')),
  },
  {
    key: 'ecommerce',
    title: 'Ecommerce',
    path: '/dashboards/ecommerce',
    component: defineAsyncComponent(() => import('@/pages/dashboards/ecommerce.vue')),
  },
  {
    key: 'academy',
    title: 'Academy',
    path: '/dashboards/academy',
    component: defineAsyncComponent(() => import('@/pages/apps/academy/dashboard.vue')),
  },
  {
    key: 'logistics',
    title: 'Logistics',
    path: '/dashboards/logistics',
    component: defineAsyncComponent(() => import('@/pages/apps/logistics/dashboard.vue')),
  },
]
</script>

<template>
  <VCard>
    <VCardTitle class="d-flex align-center justify-space-between">
      <span>Дашборды ВСЕ</span>

      <VSwitch
        v-model="showEmbedded"
        hide-details
        inset
        label="Встроенные"
      />
    </VCardTitle>

    <VCardText>
      <VRow>
        <VCol
          v-for="d in dashboards"
          :key="d.key"
          cols="12"
        >
          <VCard variant="outlined">
            <VCardTitle class="d-flex align-center justify-space-between">
              <span>{{ d.title }}</span>

              <VBtn
                :to="{ path: d.path }"
                size="small"
                variant="tonal"
              >
                Открыть отдельно
              </VBtn>
            </VCardTitle>

            <VCardText v-if="showEmbedded">
              <Suspense>
                <component :is="d.component" />
                <template #fallback>
                  <div class="text-medium-emphasis">
                    Загрузка дашборда...
                  </div>
                </template>
              </Suspense>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>

