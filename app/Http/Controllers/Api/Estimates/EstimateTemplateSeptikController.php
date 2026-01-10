<?php

namespace App\Http\Controllers\Api\Estimates;

use App\Domain\Catalog\Models\Product;
use App\Domain\Estimates\Models\EstimateTemplateMaterial;
use App\Domain\Estimates\Models\EstimateTemplateSeptik;
use App\Http\Controllers\Controller;
use App\Http\Requests\Estimates\EstimateTemplateSeptikRequest;
use App\Http\Resources\EstimateTemplateSeptikResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class EstimateTemplateSeptikController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = $perPage <= 0 ? 10 : min($perPage, 200);
        $page = (int) $request->integer('page', 1);

        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = EstimateTemplateSeptik::query()
            ->orderByDesc('updated_at');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where('title', 'like', "%{$search}%");
        }

        $sku = $request->string('sku')->toString();
        $templates = $sku
            ? (clone $query)->whereJsonContains('data', trim($sku))->paginate($perPage, ['*'], 'page', $page)
            : $query->paginate($perPage, ['*'], 'page', $page);

        if ($sku && $templates->total() === 0) {
            $normalizedSku = $this->normalizeSku($sku);
            $all = $query->get();
            $filtered = $all->filter(fn (EstimateTemplateSeptik $row) => $this->templateHasSku($row->data ?? [], $normalizedSku))
                ->values();
            $total = $filtered->count();
            $pageItems = $filtered->forPage($page, $perPage)->values();
            $templates = new LengthAwarePaginator($pageItems, $total, $perPage, $page);
        }
        if (!empty($sku)) {
            Log::info('estimate_template.septik.lookup', [
                'sku' => $sku,
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'found' => $templates->total(),
            ]);
        }
        $templateIds = collect($templates->items())
            ->flatMap(fn (EstimateTemplateSeptik $row) => $this->parseTemplateIds($row->pattern_ids))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $materialTitles = $templateIds
            ? EstimateTemplateMaterial::query()
                ->whereIn('id', $templateIds)
                ->pluck('title', 'id')
                ->all()
            : [];

        return response()->json([
            'data' => collect($templates->items())->map(function (EstimateTemplateSeptik $template) use ($materialTitles, $request) {
                $templateIds = $this->parseTemplateIds($template->pattern_ids);
                $template->setAttribute('template_ids', $templateIds);
                $template->setAttribute('template_titles', $this->resolveTemplateTitles($templateIds, $materialTitles));
                $template->setAttribute('template_id', $templateIds[0] ?? null);
                $template->setAttribute('template_title', $this->resolveTemplateTitle($templateIds, $materialTitles));

                return (new EstimateTemplateSeptikResource($template))->toArray($request);
            }),
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
        $templateIds = $this->parseTemplateIds($model->pattern_ids);
        $materialTitles = $templateIds
            ? EstimateTemplateMaterial::query()
                ->whereIn('id', $templateIds)
                ->pluck('title', 'id')
                ->all()
            : [];
        $model->setAttribute('template_ids', $templateIds);
        $model->setAttribute('template_titles', $this->resolveTemplateTitles($templateIds, $materialTitles));
        $model->setAttribute('template_id', $templateIds[0] ?? null);
        $model->setAttribute('template_title', $this->resolveTemplateTitle($templateIds, $materialTitles));

        $data = (new EstimateTemplateSeptikResource($model))->toArray($request);
        $data['items'] = $this->hydrateSkus($request, $data['skus'] ?? []);

        return response()->json([
            'data' => $data,
        ]);
    }

    public function store(EstimateTemplateSeptikRequest $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $data = $request->validated();
        $templateIds = $this->normalizeTemplateIds($data);

        $template = EstimateTemplateSeptik::query()->create([
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            'title' => $data['title'],
            'data' => $data['skus'],
            'pattern_ids' => json_encode($templateIds, JSON_THROW_ON_ERROR),
            'created_by' => $user?->id,
            'updated_by' => $user?->id,
        ]);

        $template->setAttribute('template_ids', $templateIds);
        $template->setAttribute('template_titles', $this->resolveTemplateTitles($templateIds));
        $template->setAttribute('template_id', $templateIds[0] ?? null);
        $template->setAttribute('template_title', $this->resolveTemplateTitle($templateIds));

        $data = (new EstimateTemplateSeptikResource($template))->toArray($request);
        $data['items'] = $this->hydrateSkus($request, $data['skus'] ?? []);

        return response()->json([
            'data' => $data,
        ]);
    }

    public function update(EstimateTemplateSeptikRequest $request, int $template): JsonResponse
    {
        $model = $this->resolveTemplate($request, $template);
        $data = $request->validated();
        $templateIds = $this->normalizeTemplateIds($data);

        $model->fill([
            'title' => $data['title'],
            'data' => $data['skus'],
            'pattern_ids' => json_encode($templateIds, JSON_THROW_ON_ERROR),
            'updated_by' => $request->user()?->id,
        ]);
        $model->save();

        $model->setAttribute('template_ids', $templateIds);
        $model->setAttribute('template_titles', $this->resolveTemplateTitles($templateIds));
        $model->setAttribute('template_id', $templateIds[0] ?? null);
        $model->setAttribute('template_title', $this->resolveTemplateTitle($templateIds));

        $data = (new EstimateTemplateSeptikResource($model))->toArray($request);
        $data['items'] = $this->hydrateSkus($request, $data['skus'] ?? []);

        return response()->json([
            'data' => $data,
        ]);
    }

    public function destroy(Request $request, int $template): JsonResponse
    {
        $model = $this->resolveTemplate($request, $template);
        $model->delete();

        return response()->json(['success' => true]);
    }

    private function resolveTemplate(Request $request, int $templateId): EstimateTemplateSeptik
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = EstimateTemplateSeptik::query()->where('id', $templateId);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->firstOrFail();
    }

    private function parseTemplateId(?string $patternIds): ?int
    {
        $ids = $this->parseTemplateIds($patternIds);
        return $ids[0] ?? null;
    }

    private function parseTemplateIds(?string $patternIds): array
    {
        if (!$patternIds) {
            return [];
        }

        if (is_numeric($patternIds)) {
            return [(int) $patternIds];
        }

        $decoded = json_decode($patternIds, true);
        if (is_array($decoded)) {
            return array_values(array_unique(array_map('intval', $decoded)));
        }

        preg_match_all('/\d+/', $patternIds, $matches);
        if (!empty($matches[0])) {
            return array_values(array_unique(array_map('intval', $matches[0])));
        }

        return [];
    }

    private function normalizeTemplateIds(array $data): array
    {
        $ids = $data['template_ids'] ?? [];
        if (!$ids && isset($data['template_id'])) {
            $ids = [$data['template_id']];
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    private function resolveTemplateTitles(array $templateIds, ?array $materialTitles = null): array
    {
        if ($templateIds === []) {
            return [];
        }

        $titles = $materialTitles ?? EstimateTemplateMaterial::query()
            ->whereIn('id', $templateIds)
            ->pluck('title', 'id')
            ->all();

        return array_values(array_filter(array_map(
            static fn (int $id) => $titles[$id] ?? null,
            $templateIds
        )));
    }

    private function resolveTemplateTitle(array $templateIds, ?array $materialTitles = null): ?string
    {
        $titles = $this->resolveTemplateTitles($templateIds, $materialTitles);
        return $titles[0] ?? null;
    }

    private function normalizeSku(?string $sku): string
    {
        $value = trim((string) $sku);
        if ($value === '') {
            return '';
        }
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value, 'UTF-8');
        }
        return strtolower($value);
    }

    private function templateHasSku(array $data, string $normalizedSku): bool
    {
        if ($normalizedSku === '') {
            return false;
        }
        foreach ($data as $sku) {
            if (!is_string($sku)) {
                continue;
            }
            if ($this->normalizeSku($sku) === $normalizedSku) {
                return true;
            }
        }
        return false;
    }

    private function hydrateSkus(Request $request, array $skus): array
    {
        if (!$skus) {
            return [];
        }

        $scus = collect($skus)
            ->filter()
            ->values();

        if ($scus->isEmpty()) {
            return [];
        }

        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = Product::query()->select(['id', 'scu', 'name']);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId) {
            $query->where(function ($builder) use ($companyId) {
                $builder->where('company_id', $companyId)
                    ->orWhere('is_global', true);
            });
        }

        $products = $query->whereIn('scu', $scus)->get()->keyBy('scu');

        return $scus->map(static function ($scu) use ($products) {
            $product = $products->get($scu);

            return [
                'scu' => $scu,
                'product_id' => $product?->id,
                'product_name' => $product?->name,
            ];
        })->all();
    }
}
