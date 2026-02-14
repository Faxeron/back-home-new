<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
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
            'is_paid' => ['sometimes', 'boolean'],
            'is_completed' => ['sometimes', 'boolean'],
            'finance_object_id' => ['sometimes', 'nullable', 'integer', 'exists:legacy_new.finance_objects,id'],
            'allocations' => ['sometimes', 'array', 'min:1'],
            'allocations.*.finance_object_id' => ['required_with:allocations', 'integer', 'exists:legacy_new.finance_objects,id'],
            'allocations.*.amount' => ['required_with:allocations', 'numeric', 'min:0.01'],
            'allocations.*.comment' => ['nullable', 'string', 'max:500'],
        ];
    }
}
