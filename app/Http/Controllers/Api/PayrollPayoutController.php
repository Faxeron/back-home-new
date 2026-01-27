<?php

namespace App\Http\Controllers\Api;

use App\Domain\Finance\Models\PayrollAccrual;
use App\Domain\Finance\Models\PayrollPayout;
use App\Domain\Finance\Models\PayrollPayoutItem;
use App\Domain\Finance\Models\FinanceAllocation;
use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PayrollPayoutController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = PayrollPayout::query()
            ->with(['user', 'cashbox', 'fund', 'item', 'items.accrual', 'items.contract'])
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderByDesc('id');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        $items = $query->limit(5000)->get();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'cashbox_id' => ['required', 'integer'],
            'payment_method_id' => ['required', 'integer'],
            'fund_id' => ['required', 'integer'],
            'spending_item_id' => ['required', 'integer'],
            'payout_date' => ['required', 'date'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.accrual_id' => ['required', 'integer'],
            'items.*.amount' => ['required', 'numeric', 'min:0.01'],
        ], [
            'payment_method_id.required' => 'Выберите способ оплаты.',
        ]);

        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;
        $actorId = $user?->id;

        $itemsPayload = collect($validated['items'] ?? []);
        $accrualIds = $itemsPayload->pluck('accrual_id')->map(fn ($id) => (int) $id)->unique()->values();

        $accruals = PayrollAccrual::query()
            ->whereIn('id', $accrualIds->all())
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('user_id', $validated['user_id'])
            ->get()
            ->keyBy('id');

        if ($accruals->count() !== $accrualIds->count()) {
            return response()->json(['message' => 'Не все начисления найдены.'], 422);
        }

        $totalAmount = 0.0;
        $rows = [];

        foreach ($itemsPayload as $row) {
            $accrual = $accruals->get((int) $row['accrual_id']);
            if (!$accrual) {
                return response()->json(['message' => 'Начисление не найдено.'], 422);
            }

            $amount = (float) $row['amount'];
            $accrualAmount = (float) ($accrual->amount ?? 0);
            $paidAmount = (float) ($accrual->paid_amount ?? 0);
            $remaining = $accrualAmount - $paidAmount;

            if ($accrualAmount <= 0) {
                return response()->json(['message' => 'Сумма начисления должна быть положительной.'], 422);
            }

            if ($remaining <= 0) {
                return response()->json(['message' => 'Начисление уже оплачено.'], 422);
            }

            if ($amount > $remaining) {
                return response()->json(['message' => 'Сумма выплаты превышает остаток начисления.'], 422);
            }

            $totalAmount += $amount;
            $rows[] = [
                'accrual' => $accrual,
                'amount' => $amount,
            ];
        }

        foreach ($rows as $row) {
            $accrual = $row['accrual'];
            $contractId = (int) ($accrual->contract_id ?? 0);
            if ($contractId <= 0) {
                return response()->json([
                    'message' => 'Каждое начисление должно быть привязано к договору.',
                ], 422);
            }
        }

        $finance = app(FinanceService::class);

        try {
            $payout = DB::connection('legacy_new')->transaction(function () use (
                $validated,
                $tenantId,
                $companyId,
                $actorId,
                $rows,
                $totalAmount,
                $finance
            ) {
                $payout = PayrollPayout::query()->create([
                    'tenant_id' => $tenantId,
                    'company_id' => $companyId,
                    'user_id' => $validated['user_id'],
                    'cashbox_id' => $validated['cashbox_id'],
                    'payment_method_id' => $validated['payment_method_id'],
                    'fund_id' => $validated['fund_id'],
                    'spending_item_id' => $validated['spending_item_id'],
                    'payout_date' => $validated['payout_date'],
                    'total_amount' => $totalAmount,
                    'comment' => $validated['comment'] ?? null,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);

                $spending = $finance->createSpending([
                    'tenant_id' => $tenantId,
                    'company_id' => $companyId,
                    'cashbox_id' => $validated['cashbox_id'],
                    'contract_id' => null,
                    'fond_id' => $validated['fund_id'],
                    'spending_item_id' => $validated['spending_item_id'],
                    'sum' => $totalAmount,
                    'payment_date' => $validated['payout_date'],
                    'description' => $validated['comment'] ?? 'Выплата начислений',
                    'spent_to_user_id' => $validated['user_id'],
                    'payment_method_id' => $validated['payment_method_id'],
                    'created_by_user_id' => $actorId,
                    'skip_allocation' => true,
                    'allocation_kind' => 'payroll',
                ]);

                $spendingId = $spending->id;

                foreach ($rows as $row) {
                    $accrual = $row['accrual'];
                    $amount = $row['amount'];

                    PayrollPayoutItem::query()->create([
                        'tenant_id' => $tenantId,
                        'company_id' => $companyId,
                        'payout_id' => $payout->id,
                        'accrual_id' => $accrual->id,
                        'contract_id' => $accrual->contract_id,
                        'contract_document_id' => $accrual->contract_document_id,
                        'spending_id' => $spendingId,
                        'amount' => $amount,
                    ]);

                    FinanceAllocation::query()->create([
                        'tenant_id' => $tenantId,
                        'company_id' => $companyId,
                        'spending_id' => $spendingId,
                        'contract_id' => $accrual->contract_id,
                        'amount' => $amount,
                        'kind' => 'payroll',
                        'comment' => $validated['comment'] ?? null,
                        'created_by' => $actorId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $newPaid = (float) ($accrual->paid_amount ?? 0) + $amount;
                    $accrual->paid_amount = $newPaid;
                    if ($newPaid >= (float) ($accrual->amount ?? 0)) {
                        $accrual->paid_at = now();
                        $accrual->status = 'paid';
                    } elseif (($accrual->status ?? 'active') !== 'cancelled') {
                        $accrual->status = 'active';
                    }
                    $accrual->save();
                }

                return $payout;
            });
        } catch (\RuntimeException $exception) {
            if (str_contains($exception->getMessage(), 'Insufficient funds')) {
                return response()->json(['message' => 'Недостаточно средств в кассе.'], 422);
            }
            throw $exception;
        }

        return response()->json(['data' => $payout], 201);
    }

    public function destroy(Request $request, int $payout): JsonResponse
    {
        $this->ensureAdmin($request);

        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;
        $actorId = $user?->id;

        $model = PayrollPayout::query()
            ->with('items')
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($payout);

        $finance = app(FinanceService::class);

        DB::connection('legacy_new')->transaction(function () use ($model, $finance, $tenantId, $companyId, $actorId) {
            $spendingIds = $model->items->pluck('spending_id')->filter()->unique();
            foreach ($spendingIds as $spendingId) {
                $finance->deleteSpending((int) $spendingId, (int) $tenantId, (int) $companyId, $actorId);
            }

            foreach ($model->items as $item) {
                $accrual = PayrollAccrual::query()
                    ->where('id', $item->accrual_id)
                    ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->first();
                if ($accrual) {
                    $newPaid = (float) ($accrual->paid_amount ?? 0) - (float) ($item->amount ?? 0);
                    if ($newPaid < 0) {
                        $newPaid = 0;
                    }
                    $accrual->paid_amount = $newPaid;
                    if ($newPaid < (float) ($accrual->amount ?? 0)) {
                        $accrual->paid_at = null;
                    }
                    if (($accrual->status ?? 'active') !== 'cancelled') {
                        $accrual->status = $newPaid >= (float) ($accrual->amount ?? 0) ? 'paid' : 'active';
                    }
                    $accrual->save();
                }
            }

            PayrollPayoutItem::query()->where('payout_id', $model->id)->delete();
            $model->delete();
        });

        return response()->json(['status' => 'ok']);
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'Only admins can manage payroll payouts.');
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
            abort(403, 'Only admins can manage payroll payouts.');
        }
    }
}
