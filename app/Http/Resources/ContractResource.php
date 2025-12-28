<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\CRM\Models\Contract
 */
class ContractResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $total = (float) ($this->total_amount ?? 0);
        $paidValue = $this->receipts_total ?? $this->paid_amount ?? 0;
        $paid = (float) $paidValue;
        $debt = $total - $paid;

        return [
            'id' => $this->id,
            'counterparty_id' => $this->counterparty_id,
            'counterparty' => $this->whenLoaded('counterparty', fn () => $this->counterparty ? [
                'id' => $this->counterparty->id,
                'name' => $this->counterparty->name,
                'phone' => $this->counterparty->phone,
            ] : null),
            'address' => $this->address,
            'title' => $this->title,
            'sale_type_id' => $this->sale_type_id,
            'sale_type' => $this->whenLoaded('saleType', fn () => $this->saleType ? [
                'id' => $this->saleType->id,
                'name' => $this->saleType->name,
            ] : null),
            'total_amount' => $total,
            'paid_amount' => $paid,
            'debt' => $debt,
            'system_status_code' => $this->system_status_code,
            'contract_status_id' => $this->contract_status_id,
            'status' => $this->whenLoaded('status', fn () => $this->status ? [
                'id' => $this->status->id,
                'name' => $this->status->name,
                'color' => $this->status->color,
            ] : null),
            'manager_id' => $this->manager_id,
            'manager' => $this->whenLoaded('manager', fn () => $this->manager ? [
                'id' => $this->manager->id,
                'name' => $this->manager->name,
            ] : null),
            'measurer_id' => $this->measurer_id,
            'measurer' => $this->whenLoaded('measurer', fn () => $this->measurer ? [
                'id' => $this->measurer->id,
                'name' => $this->measurer->name,
            ] : null),
            'work_start_date' => $this->work_start_date?->toDateString(),
            'work_end_date' => $this->work_end_date?->toDateString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
