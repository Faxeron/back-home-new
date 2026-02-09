<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCashflowItemRequest extends FormRequest
{
    public function rules(): array
    {
        $itemId = $this->route('cashflowItem')?->id ?? $this->route('cashflowItem');

        return [
            'tenant_id' => ['nullable', 'integer'],
            'company_id' => ['nullable', 'integer'],
            'parent_id' => ['nullable', 'integer', 'exists:legacy_new.cashflow_items,id'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:80',
                Rule::unique('legacy_new.cashflow_items', 'code')->ignore($itemId),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'section' => ['sometimes', 'required', Rule::in(['OPERATING', 'INVESTING', 'FINANCING'])],
            'direction' => ['sometimes', 'required', Rule::in(['IN', 'OUT'])],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
