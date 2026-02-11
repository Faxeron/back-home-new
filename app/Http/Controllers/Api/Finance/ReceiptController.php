<?php

namespace App\Http\Controllers\API\Finance;

use App\Http\Controllers\Controller;
use App\Domain\Finance\DTO\ReceiptFilterDTO;
use App\Domain\Finance\Models\Receipt;
use App\Domain\Finance\Models\Transaction;
use App\Http\Requests\Finance\UpdateReceiptRequest;
use App\Http\Resources\ReceiptResource;
use App\Http\Requests\Finance\CreateContractReceiptRequest;
use App\Http\Requests\Finance\CreateDirectorLoanReceiptRequest;
use App\Services\Finance\FinanceService;
use App\Services\Finance\ReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function update(UpdateReceiptRequest $request, int $receipt): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $companyId = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $record = Receipt::query()
            ->where('id', $receipt)
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Receipt not found'], 404);
        }

        $payload = $request->validated();
        if (empty($payload)) {
            return response()->json(['message' => 'No fields provided for update.'], 422);
        }

        DB::connection('legacy_new')->transaction(function () use ($record, $payload, $tenantId, $companyId) {
            $receiptUpdates = [];

            if (array_key_exists('created_at', $payload)) {
                $receiptUpdates['created_at'] = $payload['created_at'];
            }
            if (array_key_exists('updated_at', $payload)) {
                $receiptUpdates['updated_at'] = $payload['updated_at'];
            }
            if (array_key_exists('cashflow_item_id', $payload)) {
                $receiptUpdates['cashflow_item_id'] = $payload['cashflow_item_id'];
            }

            if (!array_key_exists('updated_at', $receiptUpdates)) {
                $receiptUpdates['updated_at'] = now();
            }

            if (!empty($receiptUpdates)) {
                Receipt::query()
                    ->where('id', $record->id)
                    ->where('tenant_id', $tenantId)
                    ->where('company_id', $companyId)
                    ->update($receiptUpdates);
            }

            if ($record->transaction_id) {
                $transactionUpdates = [];

                if (array_key_exists('created_at', $payload)) {
                    $transactionUpdates['created_at'] = $payload['created_at'];
                }
                if (array_key_exists('updated_at', $payload)) {
                    $transactionUpdates['updated_at'] = $payload['updated_at'];
                }
                if (array_key_exists('cashflow_item_id', $payload)) {
                    $transactionUpdates['cashflow_item_id'] = $payload['cashflow_item_id'];
                }

                if (!empty($transactionUpdates) && !array_key_exists('updated_at', $transactionUpdates)) {
                    $transactionUpdates['updated_at'] = now();
                }

                if (!empty($transactionUpdates)) {
                    Transaction::query()
                        ->where('id', $record->transaction_id)
                        ->where('tenant_id', $tenantId)
                        ->where('company_id', $companyId)
                        ->update($transactionUpdates);
                }
            }
        });

        $record->refresh()->loadMissing(['cashbox', 'company', 'counterparty', 'contract', 'creator']);

        return response()->json([
            'data' => (new ReceiptResource($record))->toArray($request),
        ]);
    }
}
