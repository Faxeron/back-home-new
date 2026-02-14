<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\Finance;

use App\Domain\Finance\Models\Transaction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\AssignTransactionFinanceObjectRequest;
use App\Http\Requests\Finance\BulkAssignTransactionsFinanceObjectRequest;
use App\Http\Resources\TransactionResource;
use App\Services\Finance\FinanceObjectAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class FinanceObjectInboxController extends Controller
{
    public function __construct(private readonly FinanceObjectAssignmentService $assignmentService)
    {
    }

    public function unassigned(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id ? (int) $request->user()?->tenant_id : null;
        $companyId = $request->user()?->default_company_id
            ? (int) $request->user()?->default_company_id
            : ($request->user()?->company_id ? (int) $request->user()?->company_id : null);

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $perPage = min(max((int) $request->integer('per_page', 50), 1), 200);
        $query = Transaction::query()
            ->with(['cashbox', 'counterparty', 'transactionType', 'paymentMethod'])
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->whereNull('finance_object_id')
            ->whereDoesntHave('financeObjectAllocations');

        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            if ($term !== '') {
                $query->where(function ($builder) use ($term): void {
                    $builder->where('notes', 'like', "%{$term}%")
                        ->orWhere('id', 'like', "%{$term}%")
                        ->orWhereHas('counterparty', function ($counterpartyQuery) use ($term): void {
                            $counterpartyQuery->where('name', 'like', "%{$term}%");
                        });
                });
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', (string) $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', (string) $request->input('date_to'));
        }

        $rows = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'data' => TransactionResource::collection($rows->items())->toArray($request),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
                'last_page' => $rows->lastPage(),
            ],
        ]);
    }

    public function assign(AssignTransactionFinanceObjectRequest $request, int $transaction): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id ? (int) $request->user()?->tenant_id : null;
        $companyId = $request->user()?->default_company_id
            ? (int) $request->user()?->default_company_id
            : ($request->user()?->company_id ? (int) $request->user()?->company_id : null);

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $model = Transaction::query()
            ->where('id', $transaction)
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->first();

        if (!$model) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $payload = $request->validated();

        try {
            $this->assignmentService->assignTransaction(
                $model,
                isset($payload['finance_object_id']) ? (int) $payload['finance_object_id'] : null,
                is_array($payload['allocations'] ?? null) ? $payload['allocations'] : [],
                $tenantId,
                $companyId,
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $model->refresh()->load(['cashbox', 'counterparty', 'contract', 'financeObject', 'transactionType', 'paymentMethod', 'financeObjectAllocations.financeObject']);

        return response()->json([
            'data' => (new TransactionResource($model))->toArray($request),
        ]);
    }

    public function bulkAssign(BulkAssignTransactionsFinanceObjectRequest $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id ? (int) $request->user()?->tenant_id : null;
        $companyId = $request->user()?->default_company_id
            ? (int) $request->user()?->default_company_id
            : ($request->user()?->company_id ? (int) $request->user()?->company_id : null);

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $payload = $request->validated();

        try {
            $updated = $this->assignmentService->bulkAssignTransactions(
                $payload['transaction_ids'],
                (int) $payload['finance_object_id'],
                $tenantId,
                $companyId,
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'status' => 'ok',
            'updated' => $updated,
        ]);
    }
}
