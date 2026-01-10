Routing (Vue auto-router)

Rules
- Do not mix a page file and a folder with the same name (e.g. contracts.vue + contracts/). It breaks route generation and layouts.
- Preferred pattern: use folder + index for nested pages:
  resources/ts/pages/operations/contracts/index.vue
  resources/ts/pages/operations/contracts/history.vue
  resources/ts/pages/operations/contracts/[id].vue
- After moving/renaming route files, restart Vite to rebuild the route map.
- Use definePage() only when you must override the default path (e.g. flat files like contracts-history.vue).
- For public pages (no app layout), set layout: 'blank' and public: true via definePage().

Why
- Vue auto-router builds a single route tree from the filesystem. A file and a folder with the same name create a conflict, so the layout and routes become unstable.
