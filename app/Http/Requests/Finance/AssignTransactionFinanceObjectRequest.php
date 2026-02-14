<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AssignTransactionFinanceObjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'finance_object_id' => ['nullable', 'integer', 'exists:legacy_new.finance_objects,id'],
            'allocations' => ['nullable', 'array', 'min:1'],
            'allocations.*.finance_object_id' => ['required_with:allocations', 'integer', 'exists:legacy_new.finance_objects,id'],
            'allocations.*.amount' => ['required_with:allocations', 'numeric', 'min:0.01'],
            'allocations.*.comment' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasObject = $this->filled('finance_object_id');
            $allocations = $this->input('allocations');
            $hasAllocations = is_array($allocations) && count($allocations) > 0;

            if ($hasObject === $hasAllocations) {
                $validator->errors()->add('finance_object_id', 'Provide either finance_object_id or allocations.');
            }
        });
    }
}

