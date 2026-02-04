<?php

namespace App\Http\Controllers\Api\Knowledge;

use App\Domain\Knowledge\Models\KnowledgeTag;
use App\Http\Controllers\Controller;
use App\Http\Resources\KnowledgeTagResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KnowledgeTagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $companyId = $this->resolveCompanyId($request);

        $query = KnowledgeTag::query()->orderBy('name');

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        $limit = (int) $request->integer('limit', 500);
        $limit = $limit <= 0 ? 200 : min($limit, 1000);

        $tags = $query->limit($limit)->get();

        return response()->json([
            'data' => KnowledgeTagResource::collection($tags)->toArray($request),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $companyId = $this->resolveCompanyId($request);
        if ($companyId === null) {
            return response()->json(['message' => 'Missing company context.'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $userId = $request->user()?->id;
        $name = (string) Str::of($validated['name'])->trim()->replaceMatches('/\s+/', ' ');

        $tag = KnowledgeTag::query()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'name' => $name,
            ],
            [
                'created_by' => $userId,
                'updated_by' => $userId,
            ],
        );

        if (!$tag->wasRecentlyCreated && $userId) {
            $tag->update(['updated_by' => $userId]);
        }

        return response()->json([
            'data' => (new KnowledgeTagResource($tag))->toArray($request),
        ], 201);
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
}
