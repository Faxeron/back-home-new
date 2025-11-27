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
        $paid = (float) ($this->paid_amount ?? 0);
        $debt = $total - $paid;

        return [
            'id' => $this->id,
            'counterparty_id' => $this->counterparty_id,
            'counterparty' => $this->whenLoaded('counterparty', fn () => [
                'id' => $this->counterparty->id,
                'name' => $this->counterparty->name,
                'phone' => $this->counterparty->phone,
            ]),
            'address' => $this->address,
            'title' => $this->title,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'debt' => $debt,
            'system_status_code' => $this->system_status_code,
            'contract_status_id' => $this->contract_status_id,
            'status' => $this->whenLoaded('status', fn () => [
                'id' => $this->status->id,
                'name' => $this->status->name,
                'color' => $this->status->color,
            ]),
            'work_start_date' => $this->work_start_date?->toDateString(),
            'work_end_date' => $this->work_end_date?->toDateString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
