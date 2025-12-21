<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class CreateSpendingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer'],
            'company_id' => ['nullable', 'integer'],
            'cash_box_id' => ['required', 'integer', 'exists:legacy_new.cash_boxes,id'],
            'payment_method_id' => ['required', 'integer', 'exists:legacy_new.payment_methods,id'],
            'sum' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'fond_id' => ['required', 'integer', 'exists:legacy_new.spending_funds,id'],
            'spending_item_id' => ['required', 'integer', 'exists:legacy_new.spending_items,id'],
            'contract_id' => ['nullable', 'integer', 'exists:legacy_new.contracts,id'],
            'counterparty_id' => ['nullable', 'integer', 'exists:legacy_new.counterparties,id'],
            'spent_to_user_id' => ['nullable', 'integer', 'exists:legacy_new.users,id'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
