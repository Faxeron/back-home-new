<?php

namespace App\Http\Resources;

use App\Domain\Finance\ValueObjects\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Finance\Models\Transaction
 */
class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'is_paid' => $this->is_paid,
            'date_is_paid' => $this->date_is_paid?->toISOString(),
            'is_completed' => $this->is_completed,
            'date_is_completed' => $this->date_is_completed?->toISOString(),
            'sum' => $this->money($this->sum),
            'cash_box_id' => $this->cash_box_id,
            'transaction_type_id' => $this->transaction_type_id,
            'payment_method_id' => $this->payment_method_id,
            'company_id' => $this->company_id,
            'counterparty_id' => $this->counterparty_id,
            'contract_id' => $this->contract_id,
            'related_id' => $this->related_id,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ]),
            'cash_box' => $this->whenLoaded('cashBox', fn () => [
                'id' => $this->cashBox->id,
                'name' => $this->cashBox->name,
            ]),
            'counterparty' => $this->whenLoaded('counterparty', fn () => [
                'id' => $this->counterparty->id,
                'name' => $this->counterparty->name ?? null,
                'phone' => $this->counterparty->phone ?? null,
            ]),
            'transaction_type' => $this->whenLoaded('transactionType', fn () => [
                'id' => $this->transactionType->id,
                'code' => $this->transactionType->code,
                'name' => $this->transactionType->name,
                'sign' => $this->transactionType->sign,
            ]),
            'payment_method' => $this->whenLoaded('paymentMethod', fn () => [
                'id' => $this->paymentMethod->id,
                'code' => $this->paymentMethod->code,
                'name' => $this->paymentMethod->name,
            ]),
        ];
    }

    private function money($value): array
    {
        if ($value instanceof Money) {
            return $value->jsonSerialize();
        }

        $normalized = number_format((float) $value, 2, '.', '');

        return [
            'amount' => $normalized,
            'currency' => 'RUB',
        ];
    }
}
