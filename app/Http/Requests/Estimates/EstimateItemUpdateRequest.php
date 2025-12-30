<?php

namespace App\Http\Requests\Estimates;

use Illuminate\Foundation\Http\FormRequest;

class EstimateItemUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'qty' => ['sometimes', 'numeric'],
            'price' => ['sometimes', 'numeric'],
        ];
    }
}
