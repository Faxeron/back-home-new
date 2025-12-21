<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class CreateContractReceiptRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer'],
            'company_id' => ['nullable', 'integer'],
            'contract_id' => ['required', 'integer', 'exists:legacy_new.contracts,id'],
            'cash_box_id' => ['required', 'integer', 'exists:legacy_new.cash_boxes,id'],
            'payment_method_id' => ['required', 'integer', 'exists:legacy_new.payment_methods,id'],
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
