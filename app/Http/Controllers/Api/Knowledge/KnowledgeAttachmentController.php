<?php

namespace App\Http\Controllers\Api\Knowledge;

use App\Domain\Knowledge\Models\KnowledgeArticle;
use App\Domain\Knowledge\Models\KnowledgeAttachment;
use App\Http\Controllers\Controller;
use App\Http\Resources\KnowledgeAttachmentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KnowledgeAttachmentController extends Controller
{
    public function store(Request $request, int $article): JsonResponse
    {
        $articleModel = $this->resolveArticle($request, $article);

        $validated = $request->validate([
            'type' => ['required', 'string', 'max:20'],
            'title' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:51200'],
        ]);

        $type = (string) Str::of($validated['type'])->trim()->lower();
        if (!in_array($type, ['file', 'link', 'video'], true)) {
            return response()->json(['message' => 'Unsupported attachment type.'], 422);
        }

        $userId = $request->user()?->id;
        $payload = [
            'tenant_id' => $articleModel->tenant_id,
            'company_id' => $articleModel->company_id,
            'article_id' => $articleModel->id,
            'type' => $type,
            'title' => $validated['title'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ];

        if ($type === 'file') {
            if (!$request->file('file')) {
                return response()->json(['message' => 'File is required.'], 422);
            }
            $file = $request->file('file');
            $directory = $this->resolveArticleDirectory($articleModel);

            $originalName = $file->getClientOriginalName();
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $safeBase = Str::slug($baseName) ?: 'file';
            $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin';
            $fileName = "{$safeBase}-" . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $extension;

            $path = $file->storeAs($directory, $fileName, 'public');

            $payload['file_path'] = $path;
            $payload['original_name'] = $originalName;
            $payload['mime_type'] = $file->getClientMimeType();
            $payload['file_size'] = $file->getSize();
        } else {
            if (empty($validated['url'])) {
                return response()->json(['message' => 'Url is required.'], 422);
            }
            $payload['url'] = $validated['url'];
        }

        $attachment = KnowledgeAttachment::query()->create($payload);

        return response()->json([
            'data' => (new KnowledgeAttachmentResource($attachment))->toArray($request),
        ], 201);
    }

    public function destroy(Request $request, int $attachment): JsonResponse
    {
        $model = $this->resolveAttachment($request, $attachment);

        if ($model->file_path) {
            Storage::disk('public')->delete($model->file_path);
        }

        $model->delete();

        return response()->json(['status' => 'ok']);
    }

    public function download(Request $request, int $attachment): StreamedResponse
    {
        $model = $this->resolveAttachment($request, $attachment);
        if (!$model->file_path) {
            abort(404);
        }

        $fileName = $model->original_name ?: basename($model->file_path);

        return Storage::disk('public')->download($model->file_path, $fileName);
    }

    private function resolveAttachment(Request $request, int $attachmentId): KnowledgeAttachment
    {
        $tenantId = $request->user()?->tenant_id;
        $companyId = $request->user()?->default_company_id ?? $request->user()?->company_id;

        $query = KnowledgeAttachment::query()->where('id', $attachmentId);
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }
        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->firstOrFail();
    }

    private function resolveArticle(Request $request, int $articleId): KnowledgeArticle
    {
        $tenantId = $request->user()?->tenant_id;
        $companyId = $request->user()?->default_company_id ?? $request->user()?->company_id;

        $query = KnowledgeArticle::query()->where('id', $articleId);
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }
        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->firstOrFail();
    }

    private function resolveArticleDirectory(KnowledgeArticle $article): string
    {
        $tenantId = $article->tenant_id ?? 0;
        $companyId = $article->company_id ?? 0;

        return "knowledge-base/tenant_{$tenantId}/company_{$companyId}/article_{$article->id}";
    }
}
