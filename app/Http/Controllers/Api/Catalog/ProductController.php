<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Requests\Catalog\ProductUpdateRequest;
use App\Domain\Catalog\DTO\ProductFilterDTO;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductRelation;
use App\Services\Catalog\CatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private readonly CatalogService $catalogService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $filters = ProductFilterDTO::fromRequest($request, $tenantId, $companyId);

        $products = $this->catalogService->paginateProducts($filters);

        return response()->json([
            'data' => ProductResource::collection($products->items())->toArray($request),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $product): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = Product::query()
            ->with([
                'category',
                'subCategory',
                'brand',
                'kind',
                'description',
                'media',
                'attributeValues.definition',
                'relations.relatedProduct',
            ])
            ->where('id', $product);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId) {
            $query->where(function ($builder) use ($companyId) {
                $builder->where('company_id', $companyId)
                    ->orWhere('is_global', true);
            });
        }

        $model = $query->firstOrFail();

        return response()->json([
            'data' => (new ProductResource($model))->toArray($request),
        ]);
    }

    public function update(ProductUpdateRequest $request, int $product): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = Product::query()
            ->where('id', $product);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId) {
            $query->where(function ($builder) use ($companyId) {
                $builder->where('company_id', $companyId)
                    ->orWhere('is_global', true);
            });
        }

        $model = $query->firstOrFail();
        $data = $request->validated();

        if (!empty($model->is_global)) {
            $priceFields = [
                'price',
                'price_sale',
                'price_vendor',
                'price_vendor_min',
                'price_zakup',
                'price_delivery',
                'montaj',
                'montaj_sebest',
            ];
            if (array_intersect($priceFields, array_keys($data))) {
                return response()->json([
                    'message' => 'Prices for global products cannot be edited.',
                ], 422);
            }
        }

        if ($request->user()) {
            $data['updated_by'] = $request->user()->id;
        }

        $model->fill($data);
        $model->save();
        if (array_key_exists('montaj_sebest', $data)) {
            $this->syncInstallationWorkPrice($model, $data['montaj_sebest'], $request->user()?->id);
        }
        $model->load(['category', 'subCategory', 'brand', 'kind']);

        return response()->json([
            'data' => (new ProductResource($model))->toArray($request),
        ]);
    }

    private function syncInstallationWorkPrice(Product $product, $montajSebest, ?int $userId): void
    {
        $relatedIds = ProductRelation::query()
            ->where('product_id', $product->id)
            ->where('relation_type', 'INSTALLATION_WORK')
            ->pluck('related_product_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($relatedIds)) {
            return;
        }

        $update = [
            'price_zakup' => $montajSebest,
            'work_kind' => 'installation_linked',
            'updated_at' => now(),
        ];

        if ($userId) {
            $update['updated_by'] = $userId;
        }

        Product::query()
            ->whereIn('id', $relatedIds)
            ->update($update);
    }
}
