<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateContractReceiptRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer'],
            'company_id' => ['nullable', 'integer'],
            'contract_id' => ['required', 'integer', 'exists:legacy_new.contracts,id'],
            'finance_object_id' => ['nullable', 'integer', 'exists:legacy_new.finance_objects,id'],
            'cashbox_id' => ['required', 'integer', 'exists:legacy_new.cashboxes,id'],
            'payment_method_id' => ['required', 'integer', 'exists:legacy_new.payment_methods,id'],
            'cashflow_item_id' => [
                'required',
                'integer',
                Rule::exists('legacy_new.cashflow_items', 'id')
                    ->where('direction', 'IN')
                    ->where('is_active', 1),
            ],
            'sum' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'counterparty_id' => ['nullable', 'integer', 'exists:legacy_new.counterparties,id'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
