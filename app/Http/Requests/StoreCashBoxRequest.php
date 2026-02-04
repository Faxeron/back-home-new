<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashBoxRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'logo' => ['nullable', 'file', 'mimes:png', 'max:2048'],
            'logo_source' => ['nullable', 'in:preset,custom'],
            'logo_preset_id' => ['nullable', 'integer', 'exists:cashbox_logos,id'],
        ];
    }
}
