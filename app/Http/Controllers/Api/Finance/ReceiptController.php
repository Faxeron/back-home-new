<?php

namespace App\Http\Controllers\API\Finance;

use App\Http\Controllers\Controller;
use App\Domain\Finance\DTO\ReceiptFilterDTO;
use App\Http\Resources\ReceiptResource;
use App\Services\Finance\ReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function __construct(private readonly ReceiptService $receiptService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id ?? $request->integer('tenant_id') ?: null;
        $filter = ReceiptFilterDTO::fromRequest($request, $tenantId);
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
}
