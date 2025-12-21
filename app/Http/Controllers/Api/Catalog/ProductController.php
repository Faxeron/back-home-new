<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Domain\Catalog\DTO\ProductFilterDTO;
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
        $tenantId = $request->user()?->tenant_id ?? $request->integer('tenant_id') ?: null;
        $companyId = $request->user()?->company_id ?? $request->integer('company_id') ?: null;

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
}
