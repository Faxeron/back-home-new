<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $filePath = $this->file_path;
        $fileName = $filePath ? basename($filePath) : null;
        $fileSize = null;
        if ($filePath && Storage::disk('local')->exists($filePath)) {
            $fileSize = Storage::disk('local')->size($filePath);
        }

        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'template_id' => $this->template_id,
            'template_name' => $this->template?->name,
            'document_type' => $this->document_type,
            'number_suffix' => $this->number_suffix,
            'file_path' => $this->file_path,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'version' => $this->version,
            'is_current' => $this->is_current,
            'generated_at' => $this->generated_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
