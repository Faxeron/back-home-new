<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Knowledge\Models\KnowledgeArticle */
class KnowledgeArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'company_id' => $this->company_id,
            'title' => $this->title,
            'body' => $this->body,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'attachments_count' => $this->when(isset($this->attachments_count), $this->attachments_count),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(
                fn ($tag) => (new KnowledgeTagResource($tag))->toArray($request),
            )),
            'topics' => $this->whenLoaded('topics', fn () => $this->topics->map(
                fn ($topic) => (new KnowledgeTopicResource($topic))->toArray($request),
            )),
            'attachments' => $this->whenLoaded('attachments', fn () => $this->attachments->map(
                fn ($attachment) => (new KnowledgeAttachmentResource($attachment))->toArray($request),
            )),
        ];
    }
}
