export const tableConfigTransactions = {
  // Endpoint without /api prefix: $api already adds baseURL /api
  endpoint: 'finance/transactions',
  include: 'cashbox,counterparty,contract,transactionType,paymentMethod',

  columns: [
    { field: 'id', label: 'ID', sortable: true, filter: 'text' },

    {
      field: 'is_paid',
      label: 'Оплачен\nДата оплаты',
      sortable: true,
      filter: 'boolean',
      body: (row: any) => `${row.is_paid ? 'Да' : 'Нет'}\n${row.date_is_paid ?? ''}`,
    },

    {
      field: 'is_completed',
      label: 'Завершён\nДата завершения',
      sortable: true,
      filter: 'boolean',
      body: (row: any) => `${row.is_completed ? 'Да' : 'Нет'}\n${row.date_is_completed ?? ''}`,
    },

    {
      field: 'transaction_type_id',
      label: 'Тип транзакции / Метод оплаты',
      filter: 'select',
      body: (row: any) => `${row.transaction_type?.name ?? row.transactionType?.name ?? ''}\n${row.payment_method?.name ?? row.paymentMethod?.name ?? ''}`,
    },

    {
      field: 'sum',
      label: 'Сумма',
      sortable: true,
      body: (row: any) => {
        const val = row.sum?.amount ?? row.sum
        return typeof val === 'number' ? val.toLocaleString('ru-RU') : val ?? ''
      },
    },

    {
      field: 'cashbox_id',
      label: 'Касса',
      filter: 'select',
      body: (row: any) => row.cashbox?.name ?? row.cashBox?.name ?? row.cash_box?.name ?? '',
    },

    {
      field: 'description',
      label: 'Описание',
      filter: 'text',
    },

    {
      field: 'contract_id',
      label: 'Договор / Контрагент',
      sortable: true,
      body: (row: any) => {
        const contract = row.contract?.number ?? ''
        const counterparty = row.counterparty?.name ?? ''
        return `${contract}\n${counterparty}`
      },
    },

    {
      field: 'related_id',
      label: 'ID связанной',
    },
  ],
}
