<?php

namespace App\Http\Requests\Contracts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['required', 'string', 'max:50'],
            'docx_template_path' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'document_type' => ['nullable', Rule::in(['supply', 'install', 'combined'])],
            'advance_mode' => ['nullable', Rule::in(['none', 'percent', 'product_types'])],
            'advance_percent' => ['nullable', 'numeric', 'min:0', 'max:100', 'required_if:advance_mode,percent'],
            'advance_product_type_ids' => ['nullable', 'array', 'required_if:advance_mode,product_types'],
            'advance_product_type_ids.*' => ['integer', Rule::exists('legacy_new.product_types', 'id')],
            'product_type_ids' => ['required', 'array', 'min:1'],
            'product_type_ids.*' => ['integer', Rule::exists('legacy_new.product_types', 'id')],
        ];
    }
}
