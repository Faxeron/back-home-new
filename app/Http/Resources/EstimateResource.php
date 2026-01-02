<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Estimates\Models\Estimate */
class EstimateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'company_id' => $this->company_id,
            'client_id' => $this->client_id,
            'client_name' => $this->client_name,
            'client_phone' => $this->client_phone,
            'site_address' => $this->site_address,
            'counterparty' => $this->whenLoaded('counterparty', fn () => $this->counterparty ? [
                'id' => $this->counterparty->id,
                'type' => $this->counterparty->type,
                'name' => $this->counterparty->name,
                'phone' => $this->counterparty->phone,
                'email' => $this->counterparty->email,
            ] : null),
            'link' => $this->link,
            'link_montaj' => $this->link_montaj,
            'random_id' => $this->random_id,
            'lead_id' => $this->lead_id,
            'amo_lead_id' => $this->amo_lead_id,
            'sms_sent' => $this->sms_sent,
            'total_sum' => $this->when(isset($this->total_sum), (float) $this->total_sum),
            'items_count' => $this->when(isset($this->items_count), (int) $this->items_count),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'items' => $this->whenLoaded('items', fn () => EstimateItemResource::collection($this->items)),
        ];
    }
}
