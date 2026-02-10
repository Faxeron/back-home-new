<?php

namespace App\Services\Finance;

use App\Domain\Finance\Models\CashBox;
use App\Domain\Finance\Models\CashTransfer;
use App\Domain\Finance\Models\CashboxHistory;
use App\Domain\Finance\Models\FinanceAllocation;
use App\Domain\Finance\Models\Receipt;
use App\Domain\Finance\Models\Spending;
use App\Domain\Finance\Models\SpendingItem;
use App\Domain\Finance\Models\Transaction;
use App\Domain\Finance\Models\TransactionType;
use App\Domain\Finance\DTO\CashTransferDTO;
use App\Domain\Finance\DTO\ReceiptDTO;
use App\Domain\Finance\DTO\SpendingDTO;
use App\Domain\Finance\DTO\TransactionDTO;
use App\Events\FinancialActionLogged;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FinanceService
{
    public function createContractReceipt(array $data): ReceiptDTO
    {
        $this->assertPositiveSum($data['sum'] ?? 0);
        $this->assertHasContract($data['contract_id'] ?? null);
        $cashboxId = $data['cashbox_id'] ?? null;
        $this->assertCashBox($cashboxId, $data['tenant_id'] ?? null, $data['company_id'] ?? null);
        return $this->transaction(function () use ($data, $cashboxId) {
            $type = $this->getTransactionType('INCOME');
            $this->lockCashBoxes([$cashboxId]);

            $receipt = Receipt::create([
                'tenant_id' => $data['tenant_id'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'cashbox_id' => $cashboxId,
                'contract_id' => $data['contract_id'] ?? null,
                'cashflow_item_id' => $data['cashflow_item_id'] ?? null,
                'sum' => $data['sum'] ?? 0,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'description' => $data['description'] ?? null,
                'counterparty_id' => $data['counterparty_id'] ?? null,
                'created_by' => $data['created_by_user_id'] ?? null,
                'created_at' => $data['created_at'] ?? now(),
            ]);

            $transaction = Transaction::create([
                'tenant_id' => $receipt->tenant_id,
                'company_id' => $receipt->company_id,
                'sum' => $receipt->sum,
                'cashbox_id' => $receipt->cashbox_id,
                'transaction_type_id' => $type->id,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'cashflow_item_id' => $data['cashflow_item_id'] ?? null,
                'counterparty_id' => $receipt->counterparty_id,
                'contract_id' => $receipt->contract_id,
                'related_id' => $receipt->id,
                'is_completed' => 0,
                'notes' => $receipt->description,
                'created_by' => $data['created_by_user_id'] ?? null,
                'created_at' => $data['created_at'] ?? now(),
            ]);

            $this->completeTransaction($transaction);

            $receipt->transaction_id = $transaction->id;
            $receipt->save();

            $this->createReceiptAllocation($receipt, $data);

            event(new FinancialActionLogged('contract_receipt.created', [
                'tenant_id' => $receipt->tenant_id,
                'company_id' => $receipt->company_id,
                'user_id' => $data['created_by_user_id'] ?? null,
                'receipt_id' => $receipt->id,
                'transaction_id' => $transaction->id,
                'contract_id' => $receipt->contract_id,
                'cashbox_id' => $receipt->cashbox_id,
                'sum' => $data['sum'] ?? 0,
            ]));

            return ReceiptDTO::fromModel($receipt);
        });
    }

    public function createDirectorLoanReceipt(array $data): ReceiptDTO
    {
        $this->assertPositiveSum($data['sum'] ?? 0);
        $cashboxId = $data['cashbox_id'] ?? null;
        $this->assertCashBox($cashboxId, $data['tenant_id'] ?? null, $data['company_id'] ?? null);
        return $this->transaction(function () use ($data, $cashboxId) {
            $type = $this->getTransactionType('DIRECTOR_LOAN');
            $this->lockCashBoxes([$cashboxId]);

            $receipt = Receipt::create([
                'tenant_id' => $data['tenant_id'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'cashbox_id' => $cashboxId,
                'contract_id' => null,
                'cashflow_item_id' => $data['cashflow_item_id'] ?? null,
                'sum' => $data['sum'] ?? 0,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'description' => $data['description'] ?? null,
                'counterparty_id' => $data['counterparty_id'] ?? null,
                'created_by' => $data['created_by_user_id'] ?? null,
                'created_at' => $data['created_at'] ?? now(),
            ]);

            $transaction = Transaction::create([
                'tenant_id' => $receipt->tenant_id,
                'company_id' => $receipt->company_id,
                'sum' => $receipt->sum,
                'cashbox_id' => $receipt->cashbox_id,
                'transaction_type_id' => $type->id,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'cashflow_item_id' => $data['cashflow_item_id'] ?? null,
                'counterparty_id' => $receipt->counterparty_id,
                'contract_id' => null,
                'related_id' => $receipt->id,
                'is_completed' => 0,
                'notes' => $receipt->description,
                'created_by' => $data['created_by_user_id'] ?? null,
                'created_at' => $data['created_at'] ?? now(),
            ]);

            $this->completeTransaction($transaction);

            $receipt->transaction_id = $transaction->id;
            $receipt->save();

            event(new FinancialActionLogged('director_loan.created', [
                'tenant_id' => $receipt->tenant_id,
                'company_id' => $receipt->company_id,
                'user_id' => $data['created_by_user_id'] ?? null,
                'receipt_id' => $receipt->id,
                'transaction_id' => $transaction->id,
                'cashbox_id' => $receipt->cashbox_id,
                'sum' => $data['sum'] ?? 0,
            ]));

            return ReceiptDTO::fromModel($receipt);
        });
    }

    public function createSpending(array $data): SpendingDTO
    {
        $this->assertPositiveSum($data['sum'] ?? 0);
        $cashboxId = $data['cashbox_id'] ?? null;
        $this->assertCashBox($cashboxId, $data['tenant_id'] ?? null, $data['company_id'] ?? null);
        return $this->transaction(function () use ($data, $cashboxId) {
            $type = $this->getTransactionType('OUTCOME');
            $this->lockCashBoxes([$cashboxId]);

            $spending = Spending::create([
                'tenant_id' => $data['tenant_id'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'cashbox_id' => $cashboxId,
                'contract_id' => $data['contract_id'] ?? null,
                'fond_id' => $data['fond_id'] ?? null,
                'spending_item_id' => $data['spending_item_id'] ?? null,
                'sum' => $data['sum'] ?? 0,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'description' => $data['description'] ?? null,
                'counterparty_id' => $data['counterparty_id'] ?? null,
                'spent_to_user_id' => $data['spent_to_user_id'] ?? null,
                'created_by' => $data['created_by_user_id'] ?? null,
                'created_at' => $data['created_at'] ?? now(),
            ]);

            $cashflowItemId = $this->resolveSpendingCashflowItemId(
                $spending->spending_item_id,
                $spending->tenant_id,
                $spending->company_id,
            );

            $transaction = Transaction::create([
                'tenant_id' => $spending->tenant_id,
                'company_id' => $spending->company_id,
                'sum' => $spending->sum,
                'cashbox_id' => $spending->cashbox_id,
                'transaction_type_id' => $type->id,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'cashflow_item_id' => $cashflowItemId,
                'counterparty_id' => $spending->counterparty_id,
                'contract_id' => $spending->contract_id,
                'related_id' => $spending->id,
                'is_completed' => 0,
                'notes' => $spending->description,
                'created_by' => $data['created_by_user_id'] ?? null,
                'created_at' => $data['created_at'] ?? now(),
            ]);

            $this->completeTransaction($transaction);

            $spending->transaction_id = $transaction->id;
            $spending->save();

            $this->createSpendingAllocation($spending, $data);

            event(new FinancialActionLogged('spending.created', [
                'tenant_id' => $spending->tenant_id,
                'company_id' => $spending->company_id,
                'user_id' => $data['created_by_user_id'] ?? null,
                'spending_id' => $spending->id,
                'transaction_id' => $transaction->id,
                'contract_id' => $spending->contract_id,
                'cashbox_id' => $spending->cashbox_id,
                'spending_item_id' => $spending->spending_item_id,
                'fund_id' => $spending->fond_id,
                'sum' => $data['sum'] ?? 0,
            ]));

            return SpendingDTO::fromModel($spending);
        });
    }

    public function deleteSpending(int $spendingId, int $tenantId, int $companyId, ?int $userId = null): void
    {
        $this->transaction(function () use ($spendingId, $tenantId, $companyId, $userId) {
            $spending = Spending::query()
                ->where('id', $spendingId)
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->first();

            if (!$spending) {
                throw new RuntimeException('Spending not found');
            }

            $transactionId = $spending->transaction_id;

            FinanceAllocation::query()
                ->where('spending_id', $spendingId)
                ->delete();

            if ($transactionId) {
                CashboxHistory::query()
                    ->where('transaction_id', $transactionId)
                    ->delete();

                Transaction::query()
                    ->where('id', $transactionId)
                    ->delete();
            }

            $spending->delete();

            event(new FinancialActionLogged('spending.deleted', [
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'user_id' => $userId,
                'spending_id' => $spendingId,
                'transaction_id' => $transactionId,
            ]));
        });
    }

    public function deleteReceipt(int $receiptId, int $tenantId, int $companyId, ?int $userId = null): void
    {
        $this->transaction(function () use ($receiptId, $tenantId, $companyId, $userId) {
            $receipt = Receipt::query()
                ->where('id', $receiptId)
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->first();

            if (!$receipt) {
                throw new RuntimeException('Receipt not found');
            }

            $transactionId = $receipt->transaction_id;

            FinanceAllocation::query()
                ->where('receipt_id', $receiptId)
                ->delete();

            if ($transactionId) {
                CashboxHistory::query()
                    ->where('transaction_id', $transactionId)
                    ->delete();

                Transaction::query()
                    ->where('id', $transactionId)
                    ->delete();
            }

            $receipt->delete();

            event(new FinancialActionLogged('receipt.deleted', [
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'user_id' => $userId,
                'receipt_id' => $receiptId,
                'transaction_id' => $transactionId,
            ]));
        });
    }

    public function createDirectorWithdrawal(array $data): SpendingDTO
    {
        $this->assertPositiveSum($data['sum'] ?? 0);
        $cashboxId = $data['cashbox_id'] ?? null;
        $this->assertCashBox($cashboxId, $data['tenant_id'] ?? null, $data['company_id'] ?? null);
        return $this->transaction(function () use ($data, $cashboxId) {
            $type = $this->getTransactionType('DIRECTOR_WITHDRAWAL');
            $this->lockCashBoxes([$cashboxId]);

            $spending = Spending::create([
                'tenant_id' => $data['tenant_id'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'cashbox_id' => $cashboxId,
                'contract_id' => null,
                'fond_id' => 1,
                'spending_item_id' => 1,
                'sum' => $data['sum'] ?? 0,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'description' => $data['description'] ?? null,
                'counterparty_id' => null,
                'spent_to_user_id' => $data['spent_to_user_id'] ?? null,
                'created_by' => $data['created_by_user_id'] ?? null,
                'created_at' => $data['created_at'] ?? now(),
            ]);

            $cashflowItemId = $this->resolveSpendingCashflowItemId(
                $spending->spending_item_id,
                $spending->tenant_id,
                $spending->company_id,
            );

            $transaction = Transaction::create([
                'tenant_id' => $spending->tenant_id,
                'company_id' => $spending->company_id,
                'sum' => $spending->sum,
                'cashbox_id' => $spending->cashbox_id,
                'transaction_type_id' => $type->id,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'cashflow_item_id' => $cashflowItemId,
                'counterparty_id' => null,
                'contract_id' => null,
                'related_id' => $spending->id,
                'is_completed' => 0,
                'notes' => $spending->description,
                'created_by' => $data['created_by_user_id'] ?? null,
                'created_at' => $data['created_at'] ?? now(),
            ]);

            $this->completeTransaction($transaction);

            $spending->transaction_id = $transaction->id;
            $spending->save();

            event(new FinancialActionLogged('director_withdrawal.created', [
                'tenant_id' => $spending->tenant_id,
                'company_id' => $spending->company_id,
                'user_id' => $data['created_by_user_id'] ?? null,
                'spending_id' => $spending->id,
                'transaction_id' => $transaction->id,
                'cashbox_id' => $spending->cashbox_id,
                'sum' => $data['sum'] ?? 0,
            ]));

            return SpendingDTO::fromModel($spending);
        });
    }

    public function transferBetweenCashBoxes(array $data): CashTransferDTO
    {
        $this->assertPositiveSum($data['sum'] ?? 0);
        $fromCashboxId = $data['from_cashbox_id'] ?? null;
        $toCashboxId = $data['to_cashbox_id'] ?? null;
        $tenantId = $data['tenant_id'] ?? null;
        $companyId = $data['company_id'] ?? null;
        $this->assertTransferBoxes($fromCashboxId, $toCashboxId);
        $this->assertCashBox($fromCashboxId, $tenantId, $companyId);
        $this->assertCashBox($toCashboxId, $tenantId, $companyId);
        return $this->transaction(function () use ($data, $fromCashboxId, $toCashboxId) {
            $typeOut = $this->getTransactionType('TRANSFER_OUT');
            $typeIn = $this->getTransactionType('TRANSFER_IN');

            $this->assertSameContext($fromCashboxId, $toCashboxId, $data['tenant_id'] ?? null, $data['company_id'] ?? null);
            $this->lockCashBoxes([$fromCashboxId, $toCashboxId]);

            $txOut = Transaction::create([
                'tenant_id' => $data['tenant_id'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'sum' => $data['sum'] ?? 0,
                'cashbox_id' => $fromCashboxId,
                'transaction_type_id' => $typeOut->id,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'related_id' => null,
                'is_completed' => 0,
                'notes' => $data['description'] ?? null,
                'created_by' => $data['created_by_user_id'] ?? null,
                'created_at' => $data['date'] ?? now(),
            ]);

            $this->completeTransaction($txOut);

            $txIn = Transaction::create([
                'tenant_id' => $data['tenant_id'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'sum' => $data['sum'] ?? 0,
                'cashbox_id' => $toCashboxId,
                'transaction_type_id' => $typeIn->id,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'related_id' => null,
                'is_completed' => 0,
                'notes' => $data['description'] ?? null,
                'created_by' => $data['created_by_user_id'] ?? null,
                'created_at' => $data['date'] ?? now(),
            ]);

            $this->completeTransaction($txIn);

            $transfer = CashTransfer::create([
                'tenant_id' => $data['tenant_id'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'from_cashbox_id' => $fromCashboxId,
                'to_cashbox_id' => $toCashboxId,
                'sum' => $data['sum'] ?? 0,
                'description' => $data['description'] ?? null,
                'transaction_out_id' => $txOut->id,
                'transaction_in_id' => $txIn->id,
                'created_by' => $data['created_by_user_id'] ?? null,
                'created_at' => $data['date'] ?? now(),
            ]);

            event(new FinancialActionLogged('cash_transfer.created', [
                'tenant_id' => $transfer->tenant_id,
                'company_id' => $transfer->company_id,
                'user_id' => $data['created_by_user_id'] ?? null,
                'cash_transfer_id' => $transfer->id,
                'transaction_out_id' => $txOut->id,
                'transaction_in_id' => $txIn->id,
                'from_cashbox_id' => $transfer->from_cashbox_id,
                'to_cashbox_id' => $transfer->to_cashbox_id,
                'sum' => $data['sum'] ?? 0,
            ]));

            return CashTransferDTO::fromModel($transfer);
        });
    }

    public function completeTransaction(Transaction $transaction): TransactionDTO
    {
        if ($transaction->is_completed) {
            throw new RuntimeException('Transaction already completed');
        }

        $this->assertCashflowItem($transaction);

        $cashboxId = $transaction->cashbox_id;
        $this->lockCashBoxes([$cashboxId]);

        $type = $this->getTransactionTypeById((int) $transaction->transaction_type_id);

        $currentBalance = $this->getCashBoxBalance((int) $cashboxId);
        $amount = $this->toFloat($transaction->sum);
        $newBalance = $currentBalance + ($amount * (int) $type->sign);

        if ((int) $type->sign < 0 && $newBalance < 0) {
            throw new RuntimeException('Insufficient funds');
        }

        $transaction->is_paid = 1;
        $transaction->date_is_paid = now();
        $transaction->is_completed = 1;
        $transaction->date_is_completed = now();
        $transaction->save();

        CashboxHistory::create([
            'tenant_id' => $transaction->tenant_id,
            'company_id' => $transaction->company_id,
            'cashbox_id' => $cashboxId,
            'transaction_id' => $transaction->id,
            'balance_after' => $newBalance,
            'created_at' => now(),
            'created_by' => $transaction->created_by ?? null,
        ]);

        return TransactionDTO::fromModel($transaction->refresh());
    }

    public function getCashBoxBalance(int $cashBoxId): float
    {
        return (float) DB::connection('legacy_new')
            ->table('transactions as t')
            ->join('transaction_types as tt', 'tt.id', '=', 't.transaction_type_id')
            ->where('t.cashbox_id', $cashBoxId)
            ->where('t.is_completed', 1)
            ->selectRaw('COALESCE(SUM(t.sum * tt.sign), 0) as balance')
            ->value('balance');
    }

    private function getTransactionType(string $code): TransactionType
    {
        $type = TransactionType::query()
            ->whereRaw('UPPER(code) = ?', [strtoupper($code)])
            ->first();

        if (!$type) {
            throw new RuntimeException(sprintf('Transaction type %s not found', $code));
        }

        return $type;
    }

    private function getTransactionTypeById(int $id): TransactionType
    {
        $type = TransactionType::query()->find($id);

        if (!$type) {
            throw new RuntimeException(sprintf('Transaction type #%d not found', $id));
        }

        return $type;
    }

    private function assertCashflowItem(Transaction $transaction): void
    {
        $type = $this->getTransactionTypeById((int) $transaction->transaction_type_id);
        $code = strtoupper((string) $type->code);

        if (in_array($code, ['TRANSFER_IN', 'TRANSFER_OUT'], true)) {
            return;
        }

        if (empty($transaction->cashflow_item_id)) {
            throw new RuntimeException('Cashflow item is required');
        }
    }

    private function toFloat($value): float
    {
        if (is_object($value) && method_exists($value, 'toFloat')) {
            return (float) $value->toFloat();
        }

        return (float) $value;
    }

    private function assertPositiveSum(float|int|string $sum): void
    {
        if ((float) $sum <= 0) {
            throw new RuntimeException('Sum must be greater than zero');
        }
    }

    private function assertHasContract(?int $contractId): void
    {
        if (!$contractId) {
            throw new RuntimeException('Contract is required for contract receipt');
        }
    }

    private function assertCashBox($cashBoxId, ?int $tenantId = null, ?int $companyId = null): void
    {
        if (!$cashBoxId) {
            throw new RuntimeException('Cash box not found');
        }

        if (!$companyId) {
            throw new RuntimeException('Company context required for cash box');
        }

        $query = CashBox::query()
            ->select('cashboxes.id')
            ->join('cashbox_company as cc', 'cc.cashbox_id', '=', 'cashboxes.id')
            ->where('cashboxes.id', $cashBoxId)
            ->where('cc.company_id', $companyId);

        if ($tenantId) {
            $query->where(function ($builder) use ($tenantId) {
                $builder->whereNull('cashboxes.tenant_id')
                    ->orWhere('cashboxes.tenant_id', $tenantId);
            });
        }

        if (!$query->exists()) {
            throw new RuntimeException('Cash box not found');
        }
    }

    private function assertTransferBoxes($fromCashBoxId, $toCashBoxId): void
    {
        if (!$fromCashBoxId || !$toCashBoxId) {
            throw new RuntimeException('Both cash boxes are required');
        }

        if ((int) $fromCashBoxId === (int) $toCashBoxId) {
            throw new RuntimeException('Cannot transfer to the same cash box');
        }
    }

    private function assertSameContext($fromCashBoxId, $toCashBoxId, ?int $tenantId = null, ?int $companyId = null): void
    {
        $query = CashBox::query()
            ->select(['cashboxes.id', 'cashboxes.tenant_id'])
            ->join('cashbox_company as cc', 'cc.cashbox_id', '=', 'cashboxes.id')
            ->whereIn('cashboxes.id', [(int) $fromCashBoxId, (int) $toCashBoxId]);

        if ($companyId) {
            $query->where('cc.company_id', $companyId);
        }

        if ($tenantId) {
            $query->where(function ($builder) use ($tenantId) {
                $builder->whereNull('cashboxes.tenant_id')
                    ->orWhere('cashboxes.tenant_id', $tenantId);
            });
        }

        $cashboxes = $query->get()->keyBy('id');

        $from = $cashboxes->get((int) $fromCashBoxId);
        $to = $cashboxes->get((int) $toCashBoxId);

        if (!$from || !$to) {
            throw new RuntimeException('Cash boxes not found');
        }

        if ($from->tenant_id !== null && $to->tenant_id !== null && (int) $from->tenant_id !== (int) $to->tenant_id) {
            throw new RuntimeException('Cash boxes belong to different tenants');
        }
    }

    private function createSpendingAllocation(Spending $spending, array $data): void
    {
        if (!empty($data['skip_allocation'])) {
            return;
        }
        if (empty($spending->contract_id)) {
            return;
        }

        FinanceAllocation::query()->firstOrCreate(
            [
                'spending_id' => $spending->id,
                'contract_id' => (int) $spending->contract_id,
            ],
            [
                'tenant_id' => $spending->tenant_id,
                'company_id' => $spending->company_id,
                'amount' => abs($this->toFloat($spending->sum)),
                'kind' => $data['allocation_kind'] ?? 'expense',
                'comment' => $spending->description,
                'created_by' => $data['created_by_user_id'] ?? null,
                'created_at' => $spending->created_at ?? now(),
                'updated_at' => $spending->created_at ?? now(),
            ]
        );
    }

    private function createReceiptAllocation(Receipt $receipt, array $data): void
    {
        if (empty($receipt->contract_id)) {
            return;
        }
        if (!empty($data['skip_allocation'])) {
            return;
        }

        FinanceAllocation::query()->firstOrCreate(
            [
                'receipt_id' => $receipt->id,
                'contract_id' => (int) $receipt->contract_id,
            ],
            [
                'tenant_id' => $receipt->tenant_id,
                'company_id' => $receipt->company_id,
                'amount' => abs($this->toFloat($receipt->sum)),
                'kind' => $data['allocation_kind'] ?? 'income',
                'comment' => $receipt->description,
                'created_by' => $data['created_by_user_id'] ?? null,
                'created_at' => $receipt->created_at ?? now(),
                'updated_at' => $receipt->created_at ?? now(),
            ]
        );
    }

    /**
     * Run a callback inside the finance connection transaction.
     */
    private function transaction(callable $callback)
    {
        return DB::connection('legacy_new')->transaction($callback);
    }

    /**
     * Lock cashbox rows to serialize balance checks.
     */
    private function lockCashBoxes(array $cashBoxIds): void
    {
        $ids = collect($cashBoxIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        DB::connection('legacy_new')
            ->table('cashboxes')
            ->whereIn('id', $ids->all())
            ->lockForUpdate()
            ->get();
    }

    private function resolveSpendingCashflowItemId(?int $spendingItemId, ?int $tenantId, ?int $companyId): int
    {
        if (!$spendingItemId) {
            throw new RuntimeException('Spending item is required');
        }

        $item = SpendingItem::query()
            ->where('id', $spendingItemId)
            ->where(function ($builder) use ($tenantId) {
                $builder->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenantId);
            })
            ->where(function ($builder) use ($companyId) {
                $builder->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            })
            ->first();

        $cashflowItemId = $item?->cashflow_item_id ? (int) $item->cashflow_item_id : null;

        if (!$cashflowItemId) {
            throw new RuntimeException('У статьи расхода не задана статья ДДС');
        }

        return $cashflowItemId;
    }
}
