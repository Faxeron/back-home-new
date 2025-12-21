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
        $tenantId = $request->user()?->tenant_id ?? $request->integer('tenant_id') ?: null;
        $companyId = $request->user()?->company_id ?? $request->integer('company_id') ?: null;
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
