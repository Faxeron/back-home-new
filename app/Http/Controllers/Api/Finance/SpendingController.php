<?php

namespace App\Http\Controllers\API\Finance;

use App\Http\Controllers\Controller;
use App\Domain\Finance\DTO\SpendingFilterDTO;
use App\Http\Resources\SpendingResource;
use App\Services\Finance\SpendingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpendingController extends Controller
{
    public function __construct(private readonly SpendingService $spendingService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id ?? $request->integer('tenant_id') ?: null;
        $filter = SpendingFilterDTO::fromRequest($request, $tenantId);
        $includes = $request->string('include')->toString() ?: null;

        $spendings = $this->spendingService->paginate($filter, $includes);

        return response()->json([
            'data' => SpendingResource::collection($spendings->items())->toArray($request),
            'meta' => [
                'current_page' => $spendings->currentPage(),
                'per_page' => $spendings->perPage(),
                'total' => $spendings->total(),
                'last_page' => $spendings->lastPage(),
            ],
        ]);
    }
}
