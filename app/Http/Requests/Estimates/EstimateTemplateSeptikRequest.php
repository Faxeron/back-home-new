<?php

namespace App\Http\Requests\Estimates;

use Illuminate\Foundation\Http\FormRequest;

class EstimateTemplateSeptikRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'template_id' => ['required_without:template_ids', 'integer'],
            'template_ids' => ['required_without:template_id', 'array', 'min:1'],
            'template_ids.*' => ['integer'],
            'skus' => ['required', 'array', 'min:1'],
            'skus.*' => ['required', 'string', 'max:255'],
        ];
    }
}
