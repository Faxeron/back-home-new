<?php

namespace App\Http\Resources;

use App\Domain\Finance\ValueObjects\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Finance\Models\Receipt
 */
class ReceiptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'cashbox_id' => $this->cashbox_id,
            'transaction_id' => $this->transaction_id,
            'sum' => $this->money($this->sum),
            'contract_id' => $this->contract_id,
            'counterparty_id' => $this->counterparty_id,
            'description' => $this->description,
            'payment_date' => $this->payment_date?->toDateString(),
            'contract' => $this->whenLoaded('contract', fn () => [
                'id' => $this->contract->id,
                'counterparty_id' => $this->contract->counterparty_id,
            ]),
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ]),
            'cashbox' => $this->whenLoaded('cashbox', fn () => [
                'id' => $this->cashbox->id,
                'name' => $this->cashbox->name,
            ]),
            'counterparty' => $this->whenLoaded('counterparty', fn () => [
                'id' => $this->counterparty->id,
                'name' => $this->counterparty->name ?? null,
                'phone' => $this->counterparty->phone ?? null,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
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
