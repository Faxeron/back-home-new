<?php

namespace App\Http\Controllers\API\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\TransactionStoreRequest;
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
        $tenantId = $request->user()?->tenant_id ?? $request->integer('tenant_id') ?: null;
        $filter = TransactionFilterDTO::fromRequest($request, $tenantId);
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

    public function store(TransactionStoreRequest $request): JsonResponse
    {
        $transaction = $this->transactionService->createIncome($request->dto());

        return response()->json(new TransactionResource($transaction), 201);
    }
}
