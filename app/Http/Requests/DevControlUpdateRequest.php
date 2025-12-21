<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DevControlUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        $status = ['nullable', 'string', 'in:OK,WIP,TODO,TBD'];

        return [
            'module' => ['sometimes', 'string'],
            'er_status' => $status,
            'model_status' => $status,
            'list_api_status' => $status,
            'crud_api_status' => $status,
            'filters_status' => $status,
            'list_ui_status' => $status,
            'form_ui_status' => $status,
            'tests_status' => $status,
            'docs_status' => $status,
            'deploy_status' => $status,
            'sort_index' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
