# DB SCHEMA (Finance slice)

## Таблицы и ключи
- transaction_types: id PK; code(varchar50) UNIQUE; name; sign tinyint; is_active bool; sort_order int. FK нет.
- payment_methods: id PK; code UNIQUE; name; is_active; sort_order. FK нет.
- cash_boxes: id PK; tenant_id (default 1 via meta migration), company_id FK->companies, name, description, is_active, created_by/updated_by FK->users, timestamps. balance столбец удалён (2025_11_22_000003).
- cash_box_company: id PK; tenant_id; company_id FK->companies; cash_box_id FK->cash_boxes; timestamps; UNIQUE(cash_box_id, company_id).
- transactions: id PK; tenant_id; company_id FK->companies; is_paid bool; date_is_paid ts nullable; is_completed bool; date_is_completed ts nullable; sum decimal(15,2); cash_box_id FK->cash_boxes; transaction_type_id FK->transaction_types; payment_method_id FK->payment_methods; notes text; counterparty_id FK->counterparties; contract_id FK->contracts; related_id FK self->transactions (nullable); created_by/updated_by FK->users; created_at/updated_at. Indexes from FKs only.
- receipts: id PK; tenant_id; company_id FK->companies; cash_box_id FK->cash_boxes; transaction_id FK->transactions; contract_id FK->contracts; sum decimal(14,2); description text; payment_date date NOT NULL default '1970-01-01'; counterparty_id FK->counterparties; created_by/updated_by FK->users; timestamps.
- spendings: id PK; tenant_id; company_id FK->companies; old_id UNIQUE; cash_box_id FK->cash_boxes; transaction_id FK->transactions; spending_item_id FK->spending_items; spending_type_id nullable; spending_type_name nullable; fond_id FK->spending_funds; contract_id FK->contracts; sum decimal(14,2); description; counterparty_id FK->counterparties; spent_to_user_id FK->users; payment_date date nullable; created_by/updated_by FK->users; timestamps.
- cash_transfers: id PK; tenant_id default 1; company_id FK->companies; from_cash_box_id FK->cash_boxes; to_cash_box_id FK->cash_boxes; sum decimal(14,2); description; transaction_out_id FK->transactions; transaction_in_id FK->transactions; created_by_user_id/created_by FK->users; created_at/updated_at. Нет уникальных ограничений.
- cashbox_history: id PK; cashbox_id FK->cash_boxes; transaction_id FK->transactions; balance_after decimal(14,2); timestamps; INDEX(cashbox_id, created_at).
- cashbox_balance_snapshots: id PK; tenant_id; company_id; cashbox_id (FK отсутствует); balance decimal(14,2); calculated_at ts; timestamps.
- spending_funds: id PK; tenant_id; company_id FK->companies; id_old UNIQUE; name; description; is_active; created_by/updated_by FK->users; timestamps.
- spending_items: id PK; tenant_id; company_id FK->companies; old_id UNIQUE; name; description; fond_id FK->spending_funds; is_active; created_by/updated_by FK->users; timestamps.
- advances: id PK; tenant_id default 1; company_id; user_id; cash_box_id; transaction_id; amount decimal(14,2); balance decimal(14,2); timestamps. FKs не заданы — нужно добавить на company_id, user_id, cash_box_id, transaction_id, tenant_id (если есть tenants), created_by/updated_by при необходимости.

## Критичные поля для целостности
- cash_box_id (transactions/receipts/spendings/cash_transfers) — связывает кассу с движением.
- tenant_id, company_id — используются для многотенантности, но FK не везде; важны для изоляции данных.
- related_id (transactions) — связь с конкретным Receipt/Spending/другим Tx; при обрыве получаются «висящие» транзакции.
- transaction_out_id / transaction_in_id (cash_transfers) — обеспечивают связь перевода с двумя транзакциями; отсутствие FK приведёт к осиротевшим переводам.
- counterparty_id / contract_id — связывают платежи с контрагентами и договорами.

## Где FK отсутствуют и нужны
- cashbox_balance_snapshots.cashbox_id — FK отсутствует, нужно cashbox_balance_snapshots.cashbox_id -> cash_boxes.id.
- advances: нет FK на company_id, user_id, cash_box_id, transaction_id (и tenant_id), их нужно добавить для целостности.
- transaction_types/payment_methods не связаны с tenants/companies — если ожидается изоляция по компании/тенанту, нужны ограничения или справочники по коду.
