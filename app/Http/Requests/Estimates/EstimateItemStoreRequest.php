<?php

namespace App\Http\Requests\Estimates;

use Illuminate\Foundation\Http\FormRequest;

class EstimateItemStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id' => ['nullable', 'integer', 'required_without:scu'],
            'scu' => ['nullable', 'string', 'max:255', 'required_without:product_id'],
            'qty' => ['required', 'numeric', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
