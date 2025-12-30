<?php

namespace App\Http\Requests\Estimates;

use Illuminate\Foundation\Http\FormRequest;

class EstimateTemplateApplyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'root_scu' => ['required', 'string', 'max:255'],
            'root_qty' => ['required', 'numeric'],
        ];
    }
}
