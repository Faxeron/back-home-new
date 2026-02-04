export type InstallationStatus = 'waiting' | 'assigned' | 'completed'

export interface InstallationRow {
  contract_id: number
  contract_title?: string | null
  counterparty_name: string | null
  address: string | null
  work_start_date: string | null
  work_end_date: string | null
  work_done_date: string | null
  worker_id: number | null
  worker_name: string | null
  status: InstallationStatus
  status_label: string
  can_edit?: boolean
}
