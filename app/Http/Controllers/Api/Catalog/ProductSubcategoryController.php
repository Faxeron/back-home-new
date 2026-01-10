<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductSubcategoryResource;
use App\Domain\Catalog\DTO\BaseFilterDTO;
use App\Services\Catalog\CatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductSubcategoryController extends Controller
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

        $categoryId = $request->integer('category_id') ?: null;

        $subcategories = $this->catalogService->paginateSubcategories($filters, $categoryId);

        return response()->json([
            'data' => ProductSubcategoryResource::collection($subcategories->items())->toArray($request),
            'meta' => [
                'current_page' => $subcategories->currentPage(),
                'per_page' => $subcategories->perPage(),
                'total' => $subcategories->total(),
                'last_page' => $subcategories->lastPage(),
            ],
        ]);
    }
}
