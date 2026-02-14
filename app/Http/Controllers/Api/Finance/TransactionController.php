<?php

namespace App\Http\Controllers\API\Finance;

use App\Http\Controllers\Controller;
use App\Domain\Finance\Models\CashboxHistory;
use App\Domain\Finance\Models\Receipt;
use App\Domain\Finance\Models\Spending;
use App\Domain\Finance\Models\Transaction;
use App\Http\Requests\Finance\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Domain\Finance\DTO\TransactionFilterDTO;
use App\Services\Finance\FinanceService;
use App\Services\Finance\FinanceObjectAssignmentService;
use App\Services\Finance\TransactionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly FinanceService $financeService,
        private readonly FinanceObjectAssignmentService $assignmentService,
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

    public function update(UpdateTransactionRequest $request, int $transaction): JsonResponse
    {
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

        $payload = $request->validated();
        if (empty($payload)) {
            return response()->json(['message' => 'No fields provided for update.'], 422);
        }

        $hasAssignmentPayload = array_key_exists('finance_object_id', $payload) || array_key_exists('allocations', $payload);

        DB::connection('legacy_new')->transaction(function () use ($payload, $record, $tenantId, $companyId) {
            $transactionUpdates = [];

            if (array_key_exists('created_at', $payload)) {
                $transactionUpdates['created_at'] = $payload['created_at'];
            }
            if (array_key_exists('updated_at', $payload)) {
                $transactionUpdates['updated_at'] = $payload['updated_at'];
            }
            if (array_key_exists('is_paid', $payload)) {
                $nextPaid = (bool) $payload['is_paid'];
                $transactionUpdates['is_paid'] = $nextPaid;
                if ($nextPaid) {
                    if (!$record->is_paid || !$record->date_is_paid) {
                        $transactionUpdates['date_is_paid'] = now()->toDateString();
                    }
                } else {
                    $transactionUpdates['date_is_paid'] = null;
                }
            }
            if (array_key_exists('is_completed', $payload)) {
                $nextCompleted = (bool) $payload['is_completed'];
                $transactionUpdates['is_completed'] = $nextCompleted;
                if ($nextCompleted) {
                    if (!$record->is_completed || !$record->date_is_completed) {
                        $transactionUpdates['date_is_completed'] = now()->toDateString();
                    }
                } else {
                    $transactionUpdates['date_is_completed'] = null;
                }
            }

            if (!array_key_exists('updated_at', $transactionUpdates)) {
                $transactionUpdates['updated_at'] = now();
            }

            if (!empty($transactionUpdates)) {
                Transaction::query()
                    ->where('id', $record->id)
                    ->where('tenant_id', $tenantId)
                    ->where('company_id', $companyId)
                    ->update($transactionUpdates);
            }

            $relatedUpdates = [];
            if (array_key_exists('created_at', $payload)) {
                $relatedUpdates['created_at'] = $payload['created_at'];
            }
            if (array_key_exists('updated_at', $payload)) {
                $relatedUpdates['updated_at'] = $payload['updated_at'];
            }

            if (!empty($relatedUpdates) && !array_key_exists('updated_at', $relatedUpdates)) {
                $relatedUpdates['updated_at'] = now();
            }

            if (!empty($relatedUpdates)) {
                Receipt::query()
                    ->where('transaction_id', $record->id)
                    ->where('tenant_id', $tenantId)
                    ->where('company_id', $companyId)
                    ->update($relatedUpdates);

                Spending::query()
                    ->where('transaction_id', $record->id)
                    ->where('tenant_id', $tenantId)
                    ->where('company_id', $companyId)
                    ->update($relatedUpdates);
            }
        });

        if ($hasAssignmentPayload) {
            try {
                $this->assignmentService->assignTransaction(
                    $record->refresh(),
                    array_key_exists('finance_object_id', $payload) && $payload['finance_object_id'] !== null
                        ? (int) $payload['finance_object_id']
                        : null,
                    is_array($payload['allocations'] ?? null) ? $payload['allocations'] : [],
                    (int) $tenantId,
                    (int) $companyId,
                );
            } catch (RuntimeException $exception) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }
        }

        $record->refresh()->loadMissing(['cashbox', 'company', 'counterparty', 'contract', 'financeObject', 'transactionType', 'paymentMethod', 'financeObjectAllocations.financeObject']);

        return response()->json([
            'data' => (new TransactionResource($record))->toArray($request),
        ]);
    }

    public function cashflowSeries(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $companyId = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        // Last 12 months including current month.
        $from = now()->subMonths(11)->startOfMonth();
        $to = now()->endOfMonth();

        $driver = DB::connection()->getDriverName();
        $monthExpr = match ($driver) {
            'pgsql' => "to_char(transactions.date_is_paid, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', transactions.date_is_paid)",
            default => "DATE_FORMAT(transactions.date_is_paid, '%Y-%m')",
        };

        /** @var Collection<int, object> $rows */
        $rows = Transaction::query()
            ->leftJoin('transaction_types as tt', 'tt.id', '=', 'transactions.transaction_type_id')
            ->where('transactions.tenant_id', $tenantId)
            ->where('transactions.company_id', $companyId)
            ->whereBetween('transactions.date_is_paid', [$from, $to])
            ->groupByRaw($monthExpr)
            ->orderByRaw($monthExpr)
            ->selectRaw("$monthExpr as ym")
            ->selectRaw('COALESCE(SUM(CASE WHEN tt.sign > 0 THEN transactions.sum ELSE 0 END), 0) as incomes_sum')
            ->selectRaw('COALESCE(SUM(CASE WHEN tt.sign < 0 THEN transactions.sum ELSE 0 END), 0) as expenses_sum')
            ->get();

        $byMonth = $rows
            ->keyBy(fn ($r) => (string) ($r->ym ?? ''))
            ->map(fn ($r) => [
                'incomes_sum' => (float) ($r->incomes_sum ?? 0),
                'expenses_sum' => (float) ($r->expenses_sum ?? 0),
            ]);

        $points = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $ym = $cursor->format('Y-m');
            $income = (float) ($byMonth->get($ym)['incomes_sum'] ?? 0);
            $expense = (float) ($byMonth->get($ym)['expenses_sum'] ?? 0);

            $points[] = [
                'month' => $ym,
                'incomes_sum' => $income,
                'expenses_sum' => $expense,
                'net_sum' => $income - $expense,
                'currency' => 'RUB',
            ];

            $cursor->addMonthNoOverflow()->startOfMonth();
        }

        return response()->json([
            'data' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'currency' => 'RUB',
                'points' => $points,
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
