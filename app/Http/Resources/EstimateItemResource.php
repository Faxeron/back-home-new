<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Estimates\Models\EstimateItem */
class EstimateItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estimate_id' => $this->estimate_id,
            'product_id' => $this->product_id,
            'qty' => $this->qty,
            'qty_auto' => $this->qty_auto,
            'qty_manual' => $this->qty_manual,
            'price' => $this->price,
            'total' => $this->total,
            'group_id' => $this->group_id,
            'sort_order' => $this->sort_order,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
                'scu' => $this->product?->scu,
                'product_type_id' => $this->product?->product_type_id,
            ]),
        ];
    }
}
