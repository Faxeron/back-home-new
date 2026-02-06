<?php

namespace App\Http\Controllers\API\Finance;

use App\Http\Controllers\Controller;
use App\Domain\Finance\Models\CashboxHistory;
use App\Domain\Finance\Models\Receipt;
use App\Domain\Finance\Models\Spending;
use App\Domain\Finance\Models\Transaction;
use App\Http\Resources\TransactionResource;
use App\Domain\Finance\DTO\TransactionFilterDTO;
use App\Services\Finance\FinanceService;
use App\Services\Finance\TransactionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
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

    public function summary(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $companyId = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        /** @var Carbon|null $from */
        $from = $request->date('date_from');
        /** @var Carbon|null $to */
        $to = $request->date('date_to');

        $from = $from ? $from->copy()->startOfDay() : now()->startOfMonth();
        $to = $to ? $to->copy()->endOfDay() : now()->endOfMonth();

        $row = Transaction::query()
            ->leftJoin('transaction_types as tt', 'tt.id', '=', 'transactions.transaction_type_id')
            ->where('transactions.tenant_id', $tenantId)
            ->where('transactions.company_id', $companyId)
            ->whereBetween('transactions.created_at', [$from, $to])
            ->selectRaw('COALESCE(SUM(CASE WHEN tt.sign > 0 THEN transactions.sum ELSE 0 END), 0) as incomes_sum')
            ->selectRaw('COALESCE(SUM(CASE WHEN tt.sign < 0 THEN transactions.sum ELSE 0 END), 0) as expenses_sum')
            ->selectRaw('COUNT(*) as transactions_count')
            ->first();

        $incomes = (float) ($row?->incomes_sum ?? 0);
        $expenses = (float) ($row?->expenses_sum ?? 0);

        return response()->json([
            'data' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'currency' => 'RUB',
                'incomes_sum' => $incomes,
                'expenses_sum' => $expenses,
                'net_sum' => $incomes - $expenses,
                'transactions_count' => (int) ($row?->transactions_count ?? 0),
            ],
        ]);
    }

    public function destroy(Request $request, int $transaction): JsonResponse
    {
        $this->ensureAdmin($request);

        $tenantId = $request->user()?->tenant_id;
        $companyId = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $record = Transaction::query()
            ->where('id', $transaction)
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $receipt = Receipt::query()
            ->where('transaction_id', $record->id)
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->first();

        if ($receipt) {
            try {
                $this->financeService->deleteReceipt($receipt->id, (int) $tenantId, (int) $companyId, $request->user()?->id);
            } catch (RuntimeException $exception) {
                if ($exception->getMessage() === 'Receipt not found') {
                    return response()->json(['message' => 'Receipt not found'], 404);
                }
                throw $exception;
            }

            return response()->json(['status' => 'ok']);
        }

        $spending = Spending::query()
            ->where('transaction_id', $record->id)
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->first();

        if ($spending) {
            try {
                $this->financeService->deleteSpending($spending->id, (int) $tenantId, (int) $companyId, $request->user()?->id);
            } catch (RuntimeException $exception) {
                if ($exception->getMessage() === 'Spending not found') {
                    return response()->json(['message' => 'Spending not found'], 404);
                }
                throw $exception;
            }

            return response()->json(['status' => 'ok']);
        }

        CashboxHistory::query()
            ->where('transaction_id', $record->id)
            ->delete();

        $this->transactionService->delete($record);

        return response()->json(['status' => 'ok']);
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'Only admins can delete.');
        }

        $userId = (int) $user->id;
        $db = DB::connection('legacy_new');
        $isAdmin = false;

        if (Schema::connection('legacy_new')->hasTable('role_users') && Schema::connection('legacy_new')->hasTable('roles')) {
            $isAdmin = $db->table('role_users')
                ->join('roles', 'roles.id', '=', 'role_users.role_id')
                ->where('role_users.user_id', $userId)
                ->where(function ($query) {
                    $query->where('roles.code', 'admin');
                })
                ->exists();
        }

        $isOwner = false;
        if (Schema::connection('legacy_new')->hasTable('user_company')) {
            $isOwner = $db->table('user_company')
                ->where('user_id', $userId)
                ->where('role', 'owner')
                ->exists();
        }

        if (!$isAdmin && !$isOwner && $userId !== 1) {
            abort(403, 'Only admins can delete.');
        }
    }
}
