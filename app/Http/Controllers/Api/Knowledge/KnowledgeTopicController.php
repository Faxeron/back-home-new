<?php

namespace App\Http\Controllers\Api\Knowledge;

use App\Domain\Knowledge\Models\KnowledgeTopic;
use App\Http\Controllers\Controller;
use App\Http\Resources\KnowledgeTopicResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KnowledgeTopicController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $companyId = $this->resolveCompanyId($request);

        $query = KnowledgeTopic::query()->orderBy('name');

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        if ($type = $request->string('type')->toString()) {
            $query->where('type', (string) Str::of($type)->trim()->lower());
        }

        if ($search = $request->string('q')->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        $limit = (int) $request->integer('limit', 500);
        $limit = $limit <= 0 ? 200 : min($limit, 1000);

        $topics = $query->limit($limit)->get();

        return response()->json([
            'data' => KnowledgeTopicResource::collection($topics)->toArray($request),
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
            'type' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $userId = $request->user()?->id;
        $type = (string) Str::of($validated['type'])->trim()->lower();
        $name = (string) Str::of($validated['name'])->trim()->replaceMatches('/\s+/', ' ');

        $topic = KnowledgeTopic::query()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'type' => $type,
                'name' => $name,
            ],
            [
                'created_by' => $userId,
                'updated_by' => $userId,
            ],
        );

        if (!$topic->wasRecentlyCreated && $userId) {
            $topic->update(['updated_by' => $userId]);
        }

        return response()->json([
            'data' => (new KnowledgeTopicResource($topic))->toArray($request),
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
