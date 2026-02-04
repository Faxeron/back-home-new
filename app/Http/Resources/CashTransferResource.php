<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Finance\Models\CashTransfer */
class CashTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'company_id' => $this->company_id,
            'from_cashbox_id' => $this->from_cashbox_id,
            'to_cashbox_id' => $this->to_cashbox_id,
            'transaction_out_id' => $this->transaction_out_id,
            'transaction_in_id' => $this->transaction_in_id,
            'sum' => $this->sum,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'from_cashbox' => $this->whenLoaded('fromCashBox', fn () => [
                'id' => $this->fromCashBox?->id,
                'name' => $this->fromCashBox?->name,
                'logo_url' => $this->cashboxLogoUrl($this->fromCashBox),
            ]),
            'to_cashbox' => $this->whenLoaded('toCashBox', fn () => [
                'id' => $this->toCashBox?->id,
                'name' => $this->toCashBox?->name,
                'logo_url' => $this->cashboxLogoUrl($this->toCashBox),
            ]),
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
