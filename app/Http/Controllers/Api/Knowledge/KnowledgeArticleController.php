<?php

namespace App\Http\Controllers\Api\Knowledge;

use App\Domain\Knowledge\Models\KnowledgeArticle;
use App\Domain\Knowledge\Models\KnowledgeTag;
use App\Domain\Knowledge\Models\KnowledgeTopic;
use App\Http\Controllers\Controller;
use App\Http\Requests\Knowledge\KnowledgeArticleStoreRequest;
use App\Http\Requests\Knowledge\KnowledgeArticleUpdateRequest;
use App\Http\Resources\KnowledgeArticleResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KnowledgeArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = $perPage <= 0 ? 10 : min($perPage, 200);
        $page = (int) $request->integer('page', 1);

        $tenantId = $this->resolveTenantId($request);
        $companyId = $this->resolveCompanyId($request);

        $query = KnowledgeArticle::query()
            ->with(['tags', 'topics'])
            ->withCount('attachments')
            ->orderByDesc('updated_at');

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                    ->orWhereHas('tags', fn (Builder $tags) => $tags->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('topics', fn (Builder $topics) => $topics->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('attachments', function (Builder $attachments) use ($search): void {
                        $attachments->where('original_name', 'like', "%{$search}%")
                            ->orWhere('title', 'like', "%{$search}%")
                            ->orWhere('url', 'like', "%{$search}%")
                            ->orWhere('search_text', 'like', "%{$search}%");
                    });
            });
        }

        $tagIds = $this->normalizeIds($request->input('tag_ids'));
        if (!empty($tagIds)) {
            $query->whereHas('tags', fn (Builder $tags) => $tags->whereIn('knowledge_tags.id', $tagIds));
        }

        $topicIds = $this->normalizeIds($request->input('topic_ids'));
        if (!empty($topicIds)) {
            $query->whereHas('topics', fn (Builder $topics) => $topics->whereIn('knowledge_topics.id', $topicIds));
        }

        if ($typeFilter = $request->string('topic_type')->toString()) {
            $query->whereHas('topics', fn (Builder $topics) => $topics->where('type', $typeFilter));
        }

        if ($request->filled('published')) {
            $query->where('is_published', $request->boolean('published'));
        }

        $articles = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => collect($articles->items())->map(
                fn (KnowledgeArticle $article) => (new KnowledgeArticleResource($article))->toArray($request),
            ),
            'meta' => [
                'current_page' => $articles->currentPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
                'last_page' => $articles->lastPage(),
            ],
        ]);
    }

    public function store(KnowledgeArticleStoreRequest $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $companyId = $this->resolveCompanyId($request);
        if ($companyId === null) {
            return response()->json(['message' => 'Missing company context.'], 403);
        }

        $data = $request->validated();
        $userId = $request->user()?->id;
        $isPublished = (bool) ($data['is_published'] ?? true);

        $article = KnowledgeArticle::query()->create([
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            'title' => $data['title'],
            'body' => $data['body'],
            'is_published' => $isPublished,
            'published_at' => $isPublished ? now() : null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $this->syncTopics($article, $data['topics'] ?? [], $tenantId, $companyId, $userId);
        $this->syncTags($article, $data['tags'] ?? [], $tenantId, $companyId, $userId);

        $article->load(['tags', 'topics', 'attachments'])->loadCount('attachments');

        return response()->json([
            'data' => (new KnowledgeArticleResource($article))->toArray($request),
        ], 201);
    }

    public function show(Request $request, int $article): JsonResponse
    {
        $model = $this->resolveArticle($request, $article);
        $model->load(['tags', 'topics', 'attachments'])->loadCount('attachments');

        return response()->json([
            'data' => (new KnowledgeArticleResource($model))->toArray($request),
        ]);
    }

    public function update(KnowledgeArticleUpdateRequest $request, int $article): JsonResponse
    {
        $model = $this->resolveArticle($request, $article);
        $data = $request->validated();
        $hasTopics = array_key_exists('topics', $data);
        $hasTags = array_key_exists('tags', $data);
        $topics = $hasTopics ? ($data['topics'] ?? []) : null;
        $tags = $hasTags ? ($data['tags'] ?? []) : null;
        unset($data['topics'], $data['tags']);
        $userId = $request->user()?->id;

        if (array_key_exists('is_published', $data)) {
            $isPublished = (bool) $data['is_published'];
            if ($isPublished && !$model->published_at) {
                $data['published_at'] = now();
            }
            if (!$isPublished) {
                $data['published_at'] = null;
            }
        }

        if ($userId) {
            $data['updated_by'] = $userId;
        }

        $model->fill($data);
        $model->save();

        if ($hasTopics) {
            $this->syncTopics(
                $model,
                $topics ?? [],
                $model->tenant_id,
                $model->company_id,
                $userId,
            );
        }

        if ($hasTags) {
            $this->syncTags(
                $model,
                $tags ?? [],
                $model->tenant_id,
                $model->company_id,
                $userId,
            );
        }

        $model->load(['tags', 'topics', 'attachments'])->loadCount('attachments');

        return response()->json([
            'data' => (new KnowledgeArticleResource($model))->toArray($request),
        ]);
    }

    public function destroy(Request $request, int $article): JsonResponse
    {
        $model = $this->resolveArticle($request, $article);
        $model->load('attachments');

        foreach ($model->attachments as $attachment) {
            if ($attachment->file_path) {
                Storage::disk('public')->delete($attachment->file_path);
            }
        }

        $model->delete();

        return response()->json(['status' => 'ok']);
    }

    private function resolveArticle(Request $request, int $articleId): KnowledgeArticle
    {
        $tenantId = $this->resolveTenantId($request);
        $companyId = $this->resolveCompanyId($request);

        $query = KnowledgeArticle::query()->where('id', $articleId);

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->firstOrFail();
    }

    private function resolveTenantId(Request $request): ?int
    {
        return $request->user()?->tenant_id;
    }

    private function resolveCompanyId(Request $request): ?int
    {
        $user = $request->user();

        return $user?->default_company_id ?? $user?->company_id;
    }

    private function normalizeIds($value): array
    {
        if (is_string($value)) {
            $value = array_filter(array_map('trim', explode(',', $value)));
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(static fn ($item) => (int) $item, $value)));
    }

    private function normalizeNames(array $names): array
    {
        return collect($names)
            ->map(fn ($name) => (string) $name)
            ->map(fn (string $name) => (string) Str::of($name)->trim()->replaceMatches('/\s+/', ' '))
            ->filter(fn (string $name) => $name !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function syncTags(
        KnowledgeArticle $article,
        array $tags,
        ?int $tenantId,
        ?int $companyId,
        ?int $userId,
    ): void {
        $names = $this->normalizeNames($tags);
        if (empty($names)) {
            $article->tags()->sync([]);
            return;
        }

        $existing = KnowledgeTag::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->whereIn('name', $names)
            ->get()
            ->keyBy('name');

        $ids = [];
        foreach ($names as $name) {
            $tag = $existing->get($name);
            if (!$tag) {
                $tag = KnowledgeTag::query()->create([
                    'tenant_id' => $tenantId,
                    'company_id' => $companyId,
                    'name' => $name,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }
            $ids[] = $tag->id;
        }

        $article->tags()->sync($ids);
    }

    private function syncTopics(
        KnowledgeArticle $article,
        array $topics,
        ?int $tenantId,
        ?int $companyId,
        ?int $userId,
    ): void {
        $items = collect($topics)
            ->filter(fn ($topic) => is_array($topic))
            ->map(fn (array $topic) => [
                'type' => (string) ($topic['type'] ?? ''),
                'name' => (string) ($topic['name'] ?? ''),
            ])
            ->map(fn (array $topic) => [
                'type' => (string) Str::of($topic['type'])->trim()->lower(),
                'name' => (string) Str::of($topic['name'])->trim()->replaceMatches('/\s+/', ' '),
            ])
            ->filter(fn (array $topic) => $topic['type'] !== '' && $topic['name'] !== '')
            ->values();

        if ($items->isEmpty()) {
            $article->topics()->sync([]);
            return;
        }

        $topicIds = [];
        $grouped = $items->groupBy('type');
        foreach ($grouped as $type => $rows) {
            $names = $rows->pluck('name')->unique()->values()->all();
            if (empty($names)) {
                continue;
            }

            $existing = KnowledgeTopic::query()
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->where('type', $type)
                ->whereIn('name', $names)
                ->get()
                ->keyBy('name');

            foreach ($names as $name) {
                $topic = $existing->get($name);
                if (!$topic) {
                    $topic = KnowledgeTopic::query()->create([
                        'tenant_id' => $tenantId,
                        'company_id' => $companyId,
                        'type' => $type,
                        'name' => $name,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                }
                $topicIds[] = $topic->id;
            }
        }

        $article->topics()->sync($topicIds);
    }
}
