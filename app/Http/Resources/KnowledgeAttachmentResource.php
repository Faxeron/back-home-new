<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Knowledge\Models\KnowledgeAttachment */
class KnowledgeAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $fileUrl = $this->file_path ? $this->publicStorageUrl($this->file_path) : null;
        $downloadUrl = $this->file_path ? "/api/knowledge/attachments/{$this->id}/download" : null;

        return [
            'id' => $this->id,
            'article_id' => $this->article_id,
            'type' => $this->type,
            'title' => $this->title,
            'url' => $this->url,
            'file_path' => $this->file_path,
            'file_url' => $fileUrl,
            'download_url' => $downloadUrl,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function publicStorageUrl(string $path): string
    {
        $normalized = ltrim($path, '/');

        return '/storage/' . $normalized;
    }
}
