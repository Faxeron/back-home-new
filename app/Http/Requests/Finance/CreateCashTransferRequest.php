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
            'from_cash_box_id' => ['required', 'integer', 'different:to_cash_box_id', 'exists:legacy_new.cash_boxes,id'],
            'to_cash_box_id' => ['required', 'integer', 'different:from_cash_box_id', 'exists:legacy_new.cash_boxes,id'],
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
