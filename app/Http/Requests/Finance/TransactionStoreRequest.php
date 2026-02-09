<?php

namespace App\Http\Requests\Finance;

use App\Domain\Finance\DTO\TransactionData;
use Illuminate\Foundation\Http\FormRequest;

class TransactionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sum' => 'required|numeric',
            'transaction_type_id' => 'required|string',
            'company_id' => 'required|integer',
            'cashbox_id' => 'nullable|integer',
            'contract_id' => 'nullable|integer',
            'counterparty_id' => 'nullable|integer',
            'payment_method_id' => 'nullable|integer',
            'cashflow_item_id' => 'nullable|integer|exists:legacy_new.cashflow_items,id',
            'notes' => 'nullable|string',
        ];
    }

    public function dto(): TransactionData
    {
        return TransactionData::fromArray($this->validated());
    }
}
