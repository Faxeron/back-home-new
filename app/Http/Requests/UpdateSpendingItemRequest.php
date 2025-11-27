<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSpendingItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'fond_id' => ['sometimes', 'required', 'integer', 'exists:legacy_new.spending_funds,id'],
            'is_active' => ['boolean'],
        ];
    }
}
