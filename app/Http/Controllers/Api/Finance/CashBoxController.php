<?php

namespace App\Http\Controllers\API\Finance;

use App\Domain\Finance\Models\CashBox;
use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceService;
use Illuminate\Http\JsonResponse;

class CashBoxController extends Controller
{
    public function __construct(private readonly FinanceService $financeService)
    {
    }

    public function index(): JsonResponse
    {
        $cashboxes = CashBox::query()->orderBy('name')->get();

        $data = $cashboxes->map(function (CashBox $cashBox) {
            return [
                'id' => $cashBox->id,
                'name' => $cashBox->name,
                'balance' => $this->financeService->getCashBoxBalance($cashBox->id),
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function balance(int $cashBoxId): JsonResponse
    {
        $balance = $this->financeService->getCashBoxBalance($cashBoxId);

        return response()->json(['balance' => $balance]);
    }
}
