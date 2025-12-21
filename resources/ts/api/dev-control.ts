import { createUrl } from '@/@core/composable/createUrl'

export const devControlEndpoint = () => 'dev-control'
export const devControlSyncDefaultsEndpoint = () => 'dev-control/sync-defaults'
export const devControlItemEndpoint = (id: number | string) => createUrl(`dev-control/${id}`)
