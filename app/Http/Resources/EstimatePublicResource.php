<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Estimates\Models\Estimate */
class EstimatePublicResource extends JsonResource
{
    private bool $hidePrices;

    public function __construct($resource, bool $hidePrices = false)
    {
        parent::__construct($resource);
        $this->hidePrices = $hidePrices;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_name' => $this->client_name,
            'client_phone' => $this->client_phone,
            'site_address' => $this->site_address,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'items_count' => $this->when(isset($this->items_count), (int) $this->items_count),
            'total_sum' => $this->when(!$this->hidePrices && isset($this->total_sum), (float) $this->total_sum),
            'items' => $this->whenLoaded('items', function () use ($request) {
                return $this->items->map(fn ($item) => (new EstimatePublicItemResource($item, $this->hidePrices))->toArray($request));
            }),
        ];
    }
}
