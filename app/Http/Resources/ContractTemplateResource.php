<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\CRM\Models\ContractTemplate
 */
class ContractTemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $productTypes = $this->whenLoaded('productTypes', fn () => $this->productTypes->map(function ($type) {
            return [
                'id' => $type->id,
                'name' => $type->name,
                'code' => $type->code ?? null,
            ];
        })->values());

        $productTypeIds = $this->whenLoaded('productTypes', fn () => $this->productTypes->pluck('id')->values());

        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'docx_template_path' => $this->docx_template_path,
            'is_active' => (bool) $this->is_active,
            'document_type' => $this->document_type,
            'advance_mode' => $this->advance_mode,
            'advance_percent' => $this->advance_percent !== null ? (float) $this->advance_percent : null,
            'advance_product_type_ids' => $this->advance_product_type_ids,
            'product_types' => $productTypes,
            'product_type_ids' => $productTypeIds,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
