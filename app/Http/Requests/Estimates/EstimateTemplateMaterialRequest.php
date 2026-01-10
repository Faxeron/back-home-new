<?php

namespace App\Http\Requests\Estimates;

use Illuminate\Foundation\Http\FormRequest;

class EstimateTemplateMaterialRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.scu' => ['required', 'string', 'max:255'],
            'items.*.count' => ['required', 'numeric', 'min:1'],
        ];
    }
}
