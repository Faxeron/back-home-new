<?php

namespace App\Http\Requests\Estimates;

use Illuminate\Foundation\Http\FormRequest;

class EstimateUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'draft' => ['sometimes', 'boolean'],
            'client_id' => ['nullable', 'integer'],
            'client_name' => ['required_unless:draft,true', 'nullable', 'string', 'max:255'],
            'client_phone' => ['required_unless:draft,true', 'nullable', 'string', 'regex:/^\\+7 \\d{3} \\d{3} \\d{2} \\d{2}$/', 'max:20'],
            'site_address' => ['nullable', 'string', 'max:255'],
        ];
    }
}
