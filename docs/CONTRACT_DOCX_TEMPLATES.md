# DOCX шаблоны договоров

Хранение файлов
- Шаблоны (DOCX): `storage/app/contracts/templates/tenant_{tenant_id}/company_{company_id}/`.
- Путь к шаблону: `contract_templates.docx_template_path`.
- Сгенерированные документы: `storage/app/contracts/documents/tenant_{tenant_id}/company_{company_id}/contract_{id}/`.

Формат плейсхолдеров
- PhpWord: `${placeholder}`.
- Пример:
  `${contract_number} от ${contract_date}`

Таблица позиций (items)
- Плейсхолдеры: `${item_index}`, `${item_scu}`, `${item_name}`, `${item_qty}`, `${item_unit}`, `${item_price}`, `${item_sum}`, `${item_group}`.
- Клонирование: `cloneRow('item_name', N)`.
- В конце добавляется строка «Итого».
- Алиасы суммы: `${item_sum}`, `${item_total}`, `${item_amount}`.

Общие плейсхолдеры
- `${contract_id}`
- `${contract_number}` / `${contract_number_base}` / `${contract_number_suffix}`
- `${document_type}`
- `${contract_date}`
- `${installation_date}`
- `${work_start_date}` / `${work_end_date}`
- `${city}` / `${site_address}`
- `${sale_type}`
- `${template_name}` / `${template_short_name}`
- `${items_count}`
- `${total_sum}` / `${total_sum_raw}` / `${total_sum_words}`

Суммы по категориям (product_types)
- `${total_products}`, `${total_materials}`, `${total_works}`, `${total_services}`, `${total_transport}`, `${total_subcontracts}`

Аванс и остаток
- `${advance_sum}` / `${advance_sum_raw}` / `${advance_sum_words}`
- `${remaining_sum}` / `${remaining_sum_raw}` / `${remaining_sum_words}`

Клиент (контрагент)
- Общие: `${client_type}`, `${client_name}`, `${client_short_name}`, `${client_phone}`, `${client_email}`
- Физлицо: `${client_first_name}`, `${client_last_name}`, `${client_patronymic}`, `${client_full_name}`,
  `${client_passport_*}`, `${client_passport_issued_at}`
- Юрлицо: `${client_legal_name}`, `${client_short_name}`, `${client_inn}`, `${client_kpp}`, `${client_ogrn}`,
  `${client_legal_address}`, `${client_postal_address}`, `${client_director_name}`, `${client_accountant_name}`,
  `${client_bank_*}`

Продавец (наша компания)
- `${seller_name}`, `${seller_short_name}`, `${seller_inn}`, `${seller_kpp}`, `${seller_ogrn}`,
  `${seller_legal_address}`, `${seller_postal_address}`, `${seller_director_name}`, `${seller_accountant_name}`,
  `${seller_bank_*}`

Примечания
- Формат дат: `ДД.ММ.ГГГГ`.
- Адрес объекта: `${site_address}`.

## REALITY STATUS
- Реально реализовано: генерация через `ContractDocumentService` + PhpWord TemplateProcessor.
- Легаси: часть шаблонов может использовать старые плейсхолдеры.
- Не сделано: валидация шаблонов на полный набор плейсхолдеров.
