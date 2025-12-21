<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCategoryResource;
use App\Domain\Catalog\DTO\BaseFilterDTO;
use App\Services\Catalog\CatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function __construct(private readonly CatalogService $catalogService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id ?? $request->integer('tenant_id') ?: null;
        $companyId = $request->user()?->company_id ?? $request->integer('company_id') ?: null;
        $filters = BaseFilterDTO::fromRequest($request, $tenantId, $companyId);

        $categories = $this->catalogService->paginateCategories($filters);

        return response()->json([
            'data' => ProductCategoryResource::collection($categories->items())->toArray($request),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'last_page' => $categories->lastPage(),
            ],
        ]);
    }
}
