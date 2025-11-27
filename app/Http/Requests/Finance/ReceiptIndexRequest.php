<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class ReceiptIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date',
            'cash_box_id' => 'sometimes|integer',
            'company_id' => 'sometimes|integer',
            'contract_id' => 'sometimes|integer',
            'counterparty_id' => 'sometimes|integer',
            'q' => 'sometimes|string',
        ];
    }

    public function filters(): array
    {
        $data = $this->validated();

        $data['per_page'] = $this->boundPerPage((int)($data['per_page'] ?? 25));
        $data['page'] = max((int)($data['page'] ?? 1), 1);

        return $data;
    }

    private function boundPerPage(int $perPage): int
    {
        $perPage = max($perPage, 1);

        return min($perPage, 100);
    }
}
