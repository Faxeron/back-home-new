<?php

namespace App\Http\Requests\Contracts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEstimateContractsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'template_ids' => ['required', 'array', 'min:1'],
            'template_ids.*' => ['integer', Rule::exists('legacy_new.contract_templates', 'id')],
            'allow_uncovered' => ['sometimes', 'boolean'],
            'counterparty_type' => ['required', Rule::in(['individual', 'company', 'legal'])],
            'counterparty' => ['required', 'array'],
            'counterparty.phone' => ['required', 'string', 'max:50'],
            'counterparty.email' => ['nullable', 'string', 'email', 'max:255'],

            'counterparty.first_name' => ['required_if:counterparty_type,individual', 'string', 'max:255'],
            'counterparty.last_name' => ['required_if:counterparty_type,individual', 'string', 'max:255'],
            'counterparty.patronymic' => ['nullable', 'string', 'max:255'],
            'counterparty.passport_series' => ['required_if:counterparty_type,individual', 'string', 'max:50'],
            'counterparty.passport_number' => ['required_if:counterparty_type,individual', 'string', 'max:50'],
            'counterparty.passport_code' => ['required_if:counterparty_type,individual', 'string', 'max:20'],
            'counterparty.passport_whom' => ['required_if:counterparty_type,individual', 'string', 'max:255'],
            'counterparty.issued_at' => ['required_if:counterparty_type,individual', 'date'],
            'counterparty.issued_by' => ['nullable', 'string', 'max:255'],

            'counterparty.legal_name' => ['required_if:counterparty_type,legal', 'required_if:counterparty_type,company', 'string', 'max:255'],
            'counterparty.short_name' => ['required_if:counterparty_type,legal', 'required_if:counterparty_type,company', 'string', 'max:255'],
            'counterparty.inn' => ['required_if:counterparty_type,legal', 'required_if:counterparty_type,company', 'string', 'max:50'],
            'counterparty.kpp' => ['required_if:counterparty_type,legal', 'required_if:counterparty_type,company', 'string', 'max:50'],
            'counterparty.ogrn' => ['required_if:counterparty_type,legal', 'required_if:counterparty_type,company', 'string', 'max:50'],
            'counterparty.legal_address' => ['required_if:counterparty_type,legal', 'required_if:counterparty_type,company', 'string', 'max:255'],
            'counterparty.postal_address' => ['required_if:counterparty_type,legal', 'required_if:counterparty_type,company', 'string', 'max:255'],
            'counterparty.director_name' => ['required_if:counterparty_type,legal', 'required_if:counterparty_type,company', 'string', 'max:255'],
            'counterparty.bank_name' => ['required_if:counterparty_type,legal', 'required_if:counterparty_type,company', 'string', 'max:255'],
            'counterparty.bik' => ['required_if:counterparty_type,legal', 'required_if:counterparty_type,company', 'string', 'max:50'],
            'counterparty.account_number' => ['required_if:counterparty_type,legal', 'required_if:counterparty_type,company', 'string', 'max:64'],
            'counterparty.correspondent_account' => ['required_if:counterparty_type,legal', 'required_if:counterparty_type,company', 'string', 'max:64'],
            'counterparty.accountant_name' => ['nullable', 'string', 'max:255'],

            'contract' => ['required', 'array'],
            'contract.contract_date' => ['required', 'date'],
            'contract.total_amount' => ['nullable', 'numeric'],
            'contract.manager_id' => ['nullable', 'integer', Rule::exists('legacy_new.users', 'id')],
            'contract.measurer_id' => ['nullable', 'integer', Rule::exists('legacy_new.users', 'id')],
            'contract.worker_id' => ['nullable', 'integer', Rule::exists('legacy_new.users', 'id')],
            'contract.city_id' => ['required', 'integer', Rule::exists('legacy_new.cities', 'id')],
            'contract.site_address' => ['required', 'string', 'max:255'],
            'contract.sale_type_id' => ['required', 'integer', Rule::exists('legacy_new.sale_types', 'id')],
            'contract.installation_date' => ['nullable', 'date'],
            'contract.work_start_date' => ['required', 'date'],
            'contract.work_end_date' => ['required', 'date', 'after_or_equal:contract.work_start_date'],
        ];
    }
}
