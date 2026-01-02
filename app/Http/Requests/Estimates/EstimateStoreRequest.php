<?php

namespace App\Http\Requests\Estimates;

use Illuminate\Foundation\Http\FormRequest;

class EstimateStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'draft' => ['sometimes', 'boolean'],
            'client_id' => ['nullable', 'integer'],
            'client_name' => ['required_without:draft', 'nullable', 'string', 'max:255'],
            'client_phone' => ['nullable', 'string', 'max:50'],
            'site_address' => ['nullable', 'string', 'max:255'],
        ];
    }
}
