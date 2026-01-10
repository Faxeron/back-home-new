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
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;
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
