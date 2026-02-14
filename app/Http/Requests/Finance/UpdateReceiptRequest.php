<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'created_at' => ['sometimes', 'nullable', 'date'],
            'updated_at' => ['sometimes', 'nullable', 'date'],
            'cashflow_item_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('legacy_new.cashflow_items', 'id')
                    ->where('direction', 'IN')
                    ->where('is_active', 1),
            ],
            'finance_object_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:legacy_new.finance_objects,id',
            ],
        ];
    }
}
