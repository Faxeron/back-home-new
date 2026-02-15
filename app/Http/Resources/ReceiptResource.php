<?php

namespace App\Http\Resources;

use App\Domain\Finance\Enums\FinanceObjectStatus;
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
            'cashflow_item_id' => $this->cashflow_item_id ?? $this->transaction?->cashflow_item_id,
            'sum' => $this->money($this->sum),
            'contract_id' => $this->contract_id,
            'finance_object_id' => $this->finance_object_id,
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
                'logo_url' => $this->cashboxLogoUrl($this->cashbox),
            ]),
            'counterparty' => $this->whenLoaded('counterparty', fn () => [
                'id' => $this->counterparty->id,
                'name' => $this->counterparty->name ?? null,
                'phone' => $this->counterparty->phone ?? null,
            ]),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name ?? $this->creator->fullName ?? null,
                'email' => $this->creator->email ?? null,
            ]),
            'finance_object' => $this->whenLoaded('financeObject', fn () => [
                'id' => $this->financeObject->id,
                'type' => $this->financeObject->type?->value ?? $this->financeObject->type,
                'name' => $this->financeObject->name,
                'code' => $this->financeObject->code,
                'status' => $this->financeObject->status?->value ?? $this->financeObject->status,
                'status_name_ru' => $this->financeObject->status instanceof FinanceObjectStatus
                    ? $this->financeObject->status->labelRu()
                    : null,
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
