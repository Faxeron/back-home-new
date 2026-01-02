<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Estimates\Models\EstimateTemplateSeptik */
class EstimateTemplateSeptikResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'company_id' => $this->company_id,
            'title' => $this->title,
            'skus' => $this->data ?? [],
            'template_ids' => $this->parseTemplateIds($this->template_ids ?? $this->pattern_ids ?? $this->template_id),
            'template_titles' => $this->template_titles ?? [],
            'template_id' => $this->parseTemplateId($this->template_id ?? $this->pattern_ids),
            'template_title' => $this->template_title ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function parseTemplateId($patternIds): ?int
    {
        $ids = $this->parseTemplateIds($patternIds);
        return $ids[0] ?? null;
    }

    private function parseTemplateIds($patternIds): array
    {
        if (!$patternIds) {
            return [];
        }

        if (is_numeric($patternIds)) {
            return [(int) $patternIds];
        }

        if (is_array($patternIds)) {
            return array_values(array_unique(array_map('intval', $patternIds)));
        }

        $decoded = json_decode((string) $patternIds, true);
        if (is_array($decoded)) {
            return array_values(array_unique(array_map('intval', $decoded)));
        }

        preg_match_all('/\d+/', (string) $patternIds, $matches);
        if (!empty($matches[0])) {
            return array_values(array_unique(array_map('intval', $matches[0])));
        }

        return [];
    }
}
