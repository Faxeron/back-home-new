<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductBrandResource;
use App\Domain\Catalog\DTO\BaseFilterDTO;
use App\Services\Catalog\CatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductBrandController extends Controller
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

        $brands = $this->catalogService->paginateBrands($filters);

        return response()->json([
            'data' => ProductBrandResource::collection($brands->items())->toArray($request),
            'meta' => [
                'current_page' => $brands->currentPage(),
                'per_page' => $brands->perPage(),
                'total' => $brands->total(),
                'last_page' => $brands->lastPage(),
            ],
        ]);
    }
}
