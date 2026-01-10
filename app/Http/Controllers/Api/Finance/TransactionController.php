<?php

namespace App\Http\Controllers\API\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Domain\Finance\DTO\TransactionFilterDTO;
use App\Services\Finance\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(private readonly TransactionService $transactionService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $companyId = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $filter = TransactionFilterDTO::fromRequest($request, $tenantId);
        $filter->tenantId = $tenantId;
        $filter->companyId = $companyId;
        $includes = $request->string('include')->toString() ?: null;

        $transactions = $this->transactionService->paginate($filter, $includes);

        return response()->json([
            'data' => TransactionResource::collection($transactions->items())->toArray($request),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'last_page' => $transactions->lastPage(),
            ],
        ]);
    }
}
