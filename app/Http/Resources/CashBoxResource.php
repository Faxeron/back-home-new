<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin \App\Domain\Finance\Models\CashBox
 */
class CashBoxResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $logoUrl = null;
        if ($this->logo_source === 'preset' && $this->logoPreset?->file_path) {
            $logoUrl = $this->publicStorageUrl($this->logoPreset->file_path);
        } elseif ($this->logo_path) {
            $logoUrl = $this->publicStorageUrl($this->logo_path);
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'company_id' => $this->company_id,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'logo_path' => $this->logo_path,
            'logo_source' => $this->logo_source,
            'logo_preset_id' => $this->logo_preset_id,
            'logo_url' => $logoUrl,
            'logo_preset' => $this->whenLoaded('logoPreset', fn () => [
                'id' => $this->logoPreset->id,
                'name' => $this->logoPreset->name,
                'file_path' => $this->logoPreset->file_path,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ]),
        ];
    }

    private function publicStorageUrl(string $path): string
    {
        $normalized = ltrim($path, '/');
        return '/storage/' . $normalized;
    }
}
