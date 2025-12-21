<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class ListCashTransfersRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer'],
            'from_cashbox_id' => ['nullable', 'integer'],
            'to_cashbox_id' => ['nullable', 'integer'],
            'from_cash_box_id' => ['nullable', 'integer'],
            'to_cash_box_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
