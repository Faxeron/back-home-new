<?php

namespace App\Http\Controllers\API\Finance;

use App\Http\Controllers\Controller;
use App\Domain\Finance\DTO\ReceiptFilterDTO;
use App\Http\Resources\ReceiptResource;
use App\Http\Requests\Finance\CreateContractReceiptRequest;
use App\Http\Requests\Finance\CreateDirectorLoanReceiptRequest;
use App\Services\Finance\FinanceService;
use App\Services\Finance\ReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function __construct(
        private readonly ReceiptService $receiptService,
        private readonly FinanceService $financeService,
    )
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $companyId = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $filter = ReceiptFilterDTO::fromRequest($request, $tenantId);
        $filter->tenantId = $tenantId;
        $filter->companyId = $companyId;
        $includes = $request->string('include')->toString() ?: null;

        $receipts = $this->receiptService->paginate($filter, $includes);

        return response()->json([
            'data' => ReceiptResource::collection($receipts->items())->toArray($request),
            'meta' => [
                'current_page' => $receipts->currentPage(),
                'per_page' => $receipts->perPage(),
                'total' => $receipts->total(),
                'last_page' => $receipts->lastPage(),
            ],
        ]);
    }

    public function storeContract(CreateContractReceiptRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['created_by_user_id'] = $request->user()?->id ?? null;
        $payload['tenant_id'] = $request->user()?->tenant_id;
        $payload['company_id'] = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$payload['tenant_id'] || !$payload['company_id']) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $receipt = $this->financeService->createContractReceipt($payload);

        return response()->json($receipt, 201);
    }

    public function storeDirectorLoan(CreateDirectorLoanReceiptRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['created_by_user_id'] = $request->user()?->id ?? null;
        $payload['tenant_id'] = $request->user()?->tenant_id;
        $payload['company_id'] = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$payload['tenant_id'] || !$payload['company_id']) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $receipt = $this->financeService->createDirectorLoanReceipt($payload);

        return response()->json($receipt, 201);
    }
}
