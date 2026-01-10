<?php

namespace App\Http\Controllers\API\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CreateDirectorWithdrawalRequest;
use App\Http\Resources\SpendingResource;
use App\Services\Finance\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DirectorController extends Controller
{
    public function __construct(private readonly FinanceService $financeService)
    {
    }

    public function withdrawal(CreateDirectorWithdrawalRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['created_by_user_id'] = $request->user()?->id ?? null;
        $payload['tenant_id'] = $request->user()?->tenant_id;
        $payload['company_id'] = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$payload['tenant_id'] || !$payload['company_id']) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $spending = $this->financeService->createDirectorWithdrawal($payload);

        return response()->json($spending, 201);
    }
}
