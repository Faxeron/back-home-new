<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\CRM\Models\ContractStatusChange
 */
class ContractStatusChangeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'previous_status' => $this->whenLoaded('previousStatus', fn () => $this->previousStatus ? [
                'id' => $this->previousStatus->id,
                'name' => $this->previousStatus->name,
                'color' => $this->previousStatus->color,
            ] : null),
            'new_status' => $this->whenLoaded('newStatus', fn () => $this->newStatus ? [
                'id' => $this->newStatus->id,
                'name' => $this->newStatus->name,
                'color' => $this->newStatus->color,
            ] : null),
            'changed_by' => $this->whenLoaded('changedBy', fn () => $this->changedBy ? [
                'id' => $this->changedBy->id,
                'name' => $this->changedBy->name,
                'email' => $this->changedBy->email,
            ] : null),
            'changed_at' => $this->changed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
