<?php

namespace App\Http\Controllers\API\Finance;

use App\Http\Controllers\Controller;
use App\Domain\Finance\DTO\SpendingFilterDTO;
use App\Http\Resources\SpendingResource;
use App\Http\Requests\Finance\CreateSpendingRequest;
use App\Services\Finance\FinanceService;
use App\Services\Finance\SpendingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpendingController extends Controller
{
    public function __construct(
        private readonly SpendingService $spendingService,
        private readonly FinanceService $financeService,
    )
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

    public function store(CreateSpendingRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['created_by_user_id'] = $request->user()?->id ?? null;
        $payload['tenant_id'] = $request->user()?->tenant_id ?? ($payload['tenant_id'] ?? null);
        $payload['company_id'] = $request->user()?->company_id ?? ($payload['company_id'] ?? null);

        $spending = $this->financeService->createSpending($payload);

        return response()->json($spending, 201);
    }
}
