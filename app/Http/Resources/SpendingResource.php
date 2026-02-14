<?php

namespace App\Http\Resources;

use App\Domain\Finance\ValueObjects\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Finance\Models\Spending
 */
class SpendingResource extends JsonResource
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
            'fond_id' => $this->fond_id,
            'spending_item_id' => $this->spending_item_id,
            'cashflow_item_id' => $this->item?->cashflow_item_id,
            'contract_id' => $this->contract_id,
            'finance_object_id' => $this->finance_object_id,
            'description' => $this->description,
            'sum' => $this->money($this->sum),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'payment_date' => $this->payment_date?->toDateString(),
            'counterparty_id' => $this->counterparty_id,
            'spent_to_user_id' => $this->spent_to_user_id,
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ]),
            'cashbox' => $this->whenLoaded('cashbox', fn () => [
                'id' => $this->cashbox->id,
                'name' => $this->cashbox->name,
                'logo_url' => $this->cashboxLogoUrl($this->cashbox),
            ]),
            'fund' => $this->whenLoaded('fund', fn () => [
                'id' => $this->fund->id,
                'name' => $this->fund->name,
            ]),
            'item' => $this->whenLoaded('item', fn () => [
                'id' => $this->item->id,
                'name' => $this->item->name,
            ]),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ]),
            'finance_object' => $this->whenLoaded('financeObject', fn () => [
                'id' => $this->financeObject->id,
                'type' => $this->financeObject->type?->value ?? $this->financeObject->type,
                'name' => $this->financeObject->name,
                'code' => $this->financeObject->code,
                'status' => $this->financeObject->status?->value ?? $this->financeObject->status,
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

    private function cashboxLogoUrl($cashbox): ?string
    {
        if (!$cashbox) {
            return null;
        }

        if ($cashbox->logo_source === 'preset' && $cashbox->logoPreset?->file_path) {
            return '/storage/' . ltrim($cashbox->logoPreset->file_path, '/');
        }

        if ($cashbox->logo_path) {
            return '/storage/' . ltrim($cashbox->logo_path, '/');
        }

        return null;
    }
}
