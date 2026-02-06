export default [
  {
    title: 'Dashboards',
    icon: { icon: 'tabler-smart-home' },
    children: [
      {
        title: 'ВСЕ',
        to: { path: '/dashboards/all' },
        icon: { icon: 'tabler-layout-dashboard' },
      },
      {
        title: 'NEW',
        to: { path: '/dashboards/new' },
        icon: { icon: 'tabler-report-money' },
        action: 'view',
        subject: 'finance',
      },
      {
        title: 'Analytics',
        to: 'dashboards-analytics',
        icon: { icon: 'tabler-chart-pie-2' },
      },
      {
        title: 'CRM',
        to: 'dashboards-crm',
        icon: { icon: 'tabler-cube' },
      },
      {
        title: 'Ecommerce',
        to: 'dashboards-ecommerce',
        icon: { icon: 'tabler-shopping-cart' },
      },
      {
        title: 'Academy',
        to: 'dashboards-academy',
        icon: { icon: 'tabler-book' },
      },
      {
        title: 'Logistics',
        to: 'dashboards-logistics',
        icon: { icon: 'tabler-truck' },
      },
    ],
  },

]
