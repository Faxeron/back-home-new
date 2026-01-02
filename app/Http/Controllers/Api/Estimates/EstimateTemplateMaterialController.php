<?php

namespace App\Http\Controllers\Api\Estimates;

use App\Domain\Catalog\Models\Product;
use App\Domain\Estimates\Models\EstimateTemplateMaterial;
use App\Http\Controllers\Controller;
use App\Http\Requests\Estimates\EstimateTemplateMaterialRequest;
use App\Http\Resources\EstimateTemplateMaterialResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstimateTemplateMaterialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = $perPage <= 0 ? 10 : min($perPage, 200);
        $page = (int) $request->integer('page', 1);

        $tenantId = $request->user()?->tenant_id ?? $request->integer('tenant_id') ?: null;
        $companyId = $request->user()?->company_id ?? $request->integer('company_id') ?: null;

        $query = EstimateTemplateMaterial::query()
            ->orderByDesc('updated_at');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId) {
            $query->where(function ($builder) use ($companyId) {
                $builder->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            });
        }

        if ($search = $request->string('q')->toString()) {
            $query->where('title', 'like', "%{$search}%");
        }

        $templates = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => collect($templates->items())->map(
                fn (EstimateTemplateMaterial $template) => (new EstimateTemplateMaterialResource($template))->toArray($request),
            ),
            'meta' => [
                'current_page' => $templates->currentPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
                'last_page' => $templates->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $template): JsonResponse
    {
        $model = $this->resolveTemplate($request, $template);

        $data = (new EstimateTemplateMaterialResource($model))->toArray($request);
        $data['items'] = $this->hydrateItems($request, $data['items'] ?? []);

        return response()->json([
            'data' => $data,
        ]);
    }

    public function store(EstimateTemplateMaterialRequest $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id ?? $request->integer('tenant_id') ?: null;
        $companyId = $request->user()?->company_id ?? $request->integer('company_id') ?: null;

        $data = $request->validated();

        $template = EstimateTemplateMaterial::query()->create([
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            'title' => $data['title'],
            'data' => $data['items'],
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $data = (new EstimateTemplateMaterialResource($template))->toArray($request);
        $data['items'] = $this->hydrateItems($request, $data['items'] ?? []);

        return response()->json([
            'data' => $data,
        ]);
    }

    public function update(EstimateTemplateMaterialRequest $request, int $template): JsonResponse
    {
        $model = $this->resolveTemplate($request, $template);
        $data = $request->validated();

        $model->fill([
            'title' => $data['title'],
            'data' => $data['items'],
            'updated_by' => $request->user()?->id,
        ]);
        $model->save();

        $data = (new EstimateTemplateMaterialResource($model))->toArray($request);
        $data['items'] = $this->hydrateItems($request, $data['items'] ?? []);

        return response()->json([
            'data' => $data,
        ]);
    }

    private function resolveTemplate(Request $request, int $templateId): EstimateTemplateMaterial
    {
        $tenantId = $request->user()?->tenant_id ?? $request->integer('tenant_id') ?: null;
        $companyId = $request->user()?->company_id ?? $request->integer('company_id') ?: null;

        $query = EstimateTemplateMaterial::query()->where('id', $templateId);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId) {
            $query->where(function ($builder) use ($companyId) {
                $builder->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            });
        }

        return $query->firstOrFail();
    }

    private function hydrateItems(Request $request, array $items): array
    {
        if (!$items) {
            return $items;
        }

        $scus = collect($items)
            ->map(fn ($item) => is_array($item) ? ($item['scu'] ?? null) : null)
            ->filter()
            ->unique()
            ->values();

        if ($scus->isEmpty()) {
            return $items;
        }

        $tenantId = $request->user()?->tenant_id ?? $request->integer('tenant_id') ?: null;
        $companyId = $request->user()?->company_id ?? $request->integer('company_id') ?: null;

        $query = Product::query()->select(['id', 'scu', 'name']);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId) {
            $query->where(function ($builder) use ($companyId) {
                $builder->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            });
        }

        $products = $query->whereIn('scu', $scus)->get()->keyBy('scu');

        return array_map(static function ($item) use ($products) {
            if (!is_array($item)) {
                return $item;
            }

            $scu = $item['scu'] ?? null;
            $product = $scu ? $products->get($scu) : null;

            return array_merge($item, [
                'product_id' => $product?->id,
                'product_name' => $product?->name,
            ]);
        }, $items);
    }
}
