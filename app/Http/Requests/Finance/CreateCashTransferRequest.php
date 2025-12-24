<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class CreateCashTransferRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer'],
            'company_id' => ['nullable', 'integer'],
            'from_cashbox_id' => ['required', 'integer', 'different:to_cashbox_id', 'exists:legacy_new.cashboxes,id'],
            'to_cashbox_id' => ['required', 'integer', 'different:from_cashbox_id', 'exists:legacy_new.cashboxes,id'],
            'sum' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'payment_method_id' => ['nullable', 'integer', 'exists:legacy_new.payment_methods,id'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
