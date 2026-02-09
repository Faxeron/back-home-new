<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCashflowItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer'],
            'company_id' => ['nullable', 'integer'],
            'parent_id' => ['nullable', 'integer', 'exists:legacy_new.cashflow_items,id'],
            'code' => ['required', 'string', 'max:80', 'unique:legacy_new.cashflow_items,code'],
            'name' => ['required', 'string', 'max:255'],
            'section' => ['required', Rule::in(['OPERATING', 'INVESTING', 'FINANCING'])],
            'direction' => ['required', Rule::in(['IN', 'OUT'])],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
