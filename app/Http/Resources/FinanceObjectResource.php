<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Finance\Models\FinanceObject
 */
class FinanceObjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'company_id' => $this->company_id,
            'type' => $this->type?->value ?? $this->type,
            'name' => $this->name,
            'code' => $this->code,
            'status' => $this->status?->value ?? $this->status,
            'date_from' => $this->date_from?->toDateString(),
            'date_to' => $this->date_to?->toDateString(),
            'counterparty_id' => $this->counterparty_id,
            'legal_contract_id' => $this->legal_contract_id,
            'description' => $this->description,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'counterparty' => $this->whenLoaded('counterparty', fn () => [
                'id' => $this->counterparty->id,
                'name' => $this->counterparty->name,
                'phone' => $this->counterparty->phone ?? null,
            ]),
            'legal_contract' => $this->whenLoaded('legalContract', fn () => [
                'id' => $this->legalContract->id,
                'title' => $this->legalContract->title,
                'contract_date' => $this->legalContract->contract_date?->toDateString(),
            ]),
            'kpi' => $this->when(isset($this->kpi), fn () => $this->kpi),
        ];
    }
}

