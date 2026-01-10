<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Domain\Catalog\DTO\BaseFilterDTO;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductKindResource;
use App\Services\Catalog\CatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductKindController extends Controller
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

        $kinds = $this->catalogService->paginateKinds($filters);

        return response()->json([
            'data' => ProductKindResource::collection($kinds->items())->toArray($request),
            'meta' => [
                'current_page' => $kinds->currentPage(),
                'per_page' => $kinds->perPage(),
                'total' => $kinds->total(),
                'last_page' => $kinds->lastPage(),
            ],
        ]);
    }
}
