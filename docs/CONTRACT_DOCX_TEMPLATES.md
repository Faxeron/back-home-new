# DOCX шаблоны договоров

## Хранение файлов
- Шаблоны (DOCX) храним в `storage/app/contracts/templates/tenant_{tenant_id}/company_{company_id}/`.
- Путь до шаблона хранится в `contract_templates.docx_template_path`.
- Сгенерированные договоры: `storage/app/contracts/documents/tenant_{tenant_id}/company_{company_id}/contract_{id}/`.

## Формат плейсхолдеров
Используем формат PhpWord: `${placeholder}`.

Пример:
```
ДОГОВОР № ${contract_number} от ${contract_date}
```

## Таблица позиций (items)
В шаблоне укажите плейсхолдеры:
`${item_index}`, `${item_scu}`, `${item_name}`, `${item_qty}`, `${item_unit}`, `${item_price}`, `${item_sum}`, `${item_group}`.

Таблица клонируется через `cloneRow('item_name', N)`.
В конце автоматически добавляется строка `Итого` с суммой в колонке `Сумма`.

Для совместимости доступны алиасы суммы:
`${item_sum}`, `${item_total}`, `${item_amount}`.

## Общие плейсхолдеры
- `${contract_id}`
- `${contract_number}`
- `${contract_date}`
- `${installation_date}`
- `${work_start_date}`
- `${work_end_date}`
- `${city}`
- `${site_address}`
- `${sale_type}`
- `${template_name}`
- `${template_short_name}`
- `${items_count}`
- `${total_sum}` (формат `1 234.56`)
- `${total_sum_raw}` (сырой `1234.56`)
- `${total_sum_words}` (сумма прописью)

## Суммы по категориям (product_types)
- `${total_products}` — товары
- `${total_materials}` — материалы
- `${total_works}` — работы
- `${total_services}` — услуги
- `${total_transport}` — транспорт
- `${total_subcontracts}` — субподряд

## Аванс и остаток
- `${advance_sum}`
- `${advance_sum_raw}`
- `${advance_sum_words}`
- `${remaining_sum}`
- `${remaining_sum_raw}`
- `${remaining_sum_words}`

## Клиент (контрагент)
Общие:
- `${client_type}` (individual/company)
- `${client_name}`
- `${client_short_name}` (Фамилия И.О. для физлица)
- `${client_phone}`
- `${client_email}`

Физлицо:
- `${client_first_name}`
- `${client_last_name}`
- `${client_patronymic}`
- `${client_full_name}`
- `${client_passport_series}`
- `${client_passport_number}`
- `${client_passport_code}`
- `${client_passport_whom}`
- `${client_passport_issued_at}`
- `${client_passport_issued_by}`
- `${client_passport_address}`

Юрлицо:
- `${client_legal_name}`
- `${client_short_name}`
- `${client_inn}`
- `${client_kpp}`
- `${client_ogrn}`
- `${client_legal_address}`
- `${client_postal_address}`
- `${client_director_name}`
- `${client_accountant_name}`
- `${client_bank_name}`
- `${client_bik}`
- `${client_account_number}`
- `${client_correspondent_account}`

## Продавец (наша компания)
- `${seller_name}`
- `${seller_short_name}`
- `${seller_inn}`
- `${seller_kpp}`
- `${seller_ogrn}`
- `${seller_legal_address}`
- `${seller_postal_address}`
- `${seller_director_name}`
- `${seller_accountant_name}`
- `${seller_bank_name}`
- `${seller_bik}`
- `${seller_account_number}`
- `${seller_correspondent_account}`

## Примечания
- Для адреса объекта используйте `${site_address}` (бывший `${kooperativ}`).
- Формат дат в DOCX: `ДД.ММ.ГГГГ`.
