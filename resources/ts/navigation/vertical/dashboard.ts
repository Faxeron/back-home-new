export default [
  {
    title: 'Dashboards',
    icon: { icon: 'tabler-smart-home' },
    children: [
      {
        title: 'Analytics',
        to: 'dashboards-analytics',
      },
      {
        title: 'CRM',
        to: 'dashboards-crm',
      },
      {
        title: 'Ecommerce',
        to: 'dashboards-ecommerce',
      },
      {
        title: 'Academy',
        to: 'dashboards-academy',
      },
      {
        title: 'Logistics',
        to: 'dashboards-logistics',
      },
    ],
    badgeContent: '5',
    badgeClass: 'bg-error',
  },
  {
    title: 'Front Pages',
    icon: { icon: 'tabler-files' },
    children: [
      {
        title: 'Landing',
        to: 'front-pages-landing-page',
        target: '_blank',
      },
      {
        title: 'Pricing',
        to: 'front-pages-pricing',
        target: '_blank',
      },
      {
        title: 'Payment',
        to: 'front-pages-payment',
        target: '_blank',
      },
      {
        title: 'Checkout',
        to: 'front-pages-checkout',
        target: '_blank',
      },
      {
        title: 'Help Center',
        to: 'front-pages-help-center',
        target: '_blank',
      },
    ],
  },
  { heading: 'Продажи' },
  { title: 'Сметы', icon: { icon: 'tabler-file-invoice' }, to: { path: '/estimates' } },
  { title: 'Договоры', icon: { icon: 'tabler-briefcase' }, to: { path: '/operations/contracts' } },
  { title: 'Замеры', icon: { icon: 'tabler-ruler-measure' }, to: { path: '/operations/measurements' } },
  { title: 'Монтажи', icon: { icon: 'tabler-tools' }, to: { path: '/operations/installations' } },
]
