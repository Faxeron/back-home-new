<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Estimates\Models\EstimateItem */
class EstimatePublicItemResource extends JsonResource
{
    private bool $hidePrices;

    public function __construct($resource, bool $hidePrices = false)
    {
        parent::__construct($resource);
        $this->hidePrices = $hidePrices;
    }

    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'estimate_id' => $this->estimate_id,
            'product_id' => $this->product_id,
            'qty' => $this->qty,
            'group_id' => $this->group_id,
            'sort_order' => $this->sort_order,
            'group' => $this->whenLoaded('group', fn () => [
                'id' => $this->group?->id,
                'name' => $this->group?->name,
            ]),
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
                'scu' => $this->product?->scu,
                'product_type_id' => $this->product?->product_type_id,
                'unit' => $this->product?->unit ? [
                    'id' => $this->product->unit->id,
                    'code' => $this->product->unit->code ?? null,
                    'name' => $this->product->unit->name ?? null,
                ] : null,
            ]),
        ];

        if (!$this->hidePrices) {
            $data['price'] = $this->price;
            $data['total'] = $this->total;
        }

        return $data;
    }
}
