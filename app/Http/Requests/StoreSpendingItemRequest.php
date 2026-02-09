<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSpendingItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'fond_id' => ['required', 'integer', 'exists:legacy_new.spending_funds,id'],
            'cashflow_item_id' => [
                'nullable',
                'integer',
                Rule::exists('legacy_new.cashflow_items', 'id')
                    ->where('direction', 'OUT')
                    ->where('is_active', 1),
            ],
            'is_active' => ['boolean'],
        ];
    }
}
