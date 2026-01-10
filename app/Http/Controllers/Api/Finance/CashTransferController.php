<?php

namespace App\Http\Controllers\API\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CreateCashTransferRequest;
use App\Http\Requests\Finance\ListCashTransfersRequest;
use App\Http\Resources\CashTransferResource;
use App\Services\Finance\CashTransferService;
use App\Services\Finance\FinanceService;
use Illuminate\Http\JsonResponse;

class CashTransferController extends Controller
{
    public function __construct(
        private readonly FinanceService $financeService,
        private readonly CashTransferService $cashTransferService,
    ) {
    }

    public function index(ListCashTransfersRequest $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $companyId = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $filters = $request->validated();
        $filters['tenant_id'] = $tenantId;
        $filters['company_id'] = $companyId;

        $transfers = $this->cashTransferService->paginate($filters);

        return response()->json([
            'data' => CashTransferResource::collection($transfers->items())->toArray($request),
            'meta' => [
                'current_page' => $transfers->currentPage(),
                'per_page' => $transfers->perPage(),
                'total' => $transfers->total(),
                'last_page' => $transfers->lastPage(),
            ],
        ]);
    }

    public function store(CreateCashTransferRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['created_by_user_id'] = $request->user()?->id ?? null;
        $payload['tenant_id'] = $request->user()?->tenant_id;
        $payload['company_id'] = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$payload['tenant_id'] || !$payload['company_id']) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $transfer = $this->financeService->transferBetweenCashBoxes($payload);

        return response()->json($transfer, 201);
    }
}
