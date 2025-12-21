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
        $tenantId = $request->user()?->tenant_id ?? $request->integer('tenant_id') ?: null;
        $companyId = $request->user()?->company_id ?? $request->integer('company_id') ?: null;
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
