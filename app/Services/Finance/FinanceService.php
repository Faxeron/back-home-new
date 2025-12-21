<?php

namespace App\Services\Finance;

use App\Domain\Finance\Models\CashBox;
use App\Domain\Finance\Models\CashTransfer;
use App\Domain\Finance\Models\CashboxHistory;
use App\Domain\Finance\Models\Receipt;
use App\Domain\Finance\Models\Spending;
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
        $this->assertCashBox($data['cash_box_id'] ?? null);
        return $this->transaction(function () use ($data) {
            $type = $this->getTransactionType('INCOME');
            $this->lockCashBoxes([$data['cash_box_id'] ?? null]);

            $receipt = Receipt::create([
                'tenant_id' => $data['tenant_id'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'cash_box_id' => $data['cash_box_id'] ?? null,
                'contract_id' => $data['contract_id'] ?? null,
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
                'cash_box_id' => $receipt->cash_box_id,
                'transaction_type_id' => $type->id,
                'payment_method_id' => $data['payment_method_id'] ?? null,
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

            event(new FinancialActionLogged('contract_receipt.created', ['receipt_id' => $receipt->id]));

            return ReceiptDTO::fromModel($receipt);
        });
    }

    public function createDirectorLoanReceipt(array $data): ReceiptDTO
    {
        $this->assertPositiveSum($data['sum'] ?? 0);
        $this->assertCashBox($data['cash_box_id'] ?? null);
        return $this->transaction(function () use ($data) {
            $type = $this->getTransactionType('DIRECTOR_LOAN');
            $this->lockCashBoxes([$data['cash_box_id'] ?? null]);

            $receipt = Receipt::create([
                'tenant_id' => $data['tenant_id'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'cash_box_id' => $data['cash_box_id'] ?? null,
                'contract_id' => null,
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
                'cash_box_id' => $receipt->cash_box_id,
                'transaction_type_id' => $type->id,
                'payment_method_id' => $data['payment_method_id'] ?? null,
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

            event(new FinancialActionLogged('director_loan.created', ['receipt_id' => $receipt->id]));

            return ReceiptDTO::fromModel($receipt);
        });
    }

    public function createSpending(array $data): SpendingDTO
    {
        $this->assertPositiveSum($data['sum'] ?? 0);
        $this->assertCashBox($data['cash_box_id'] ?? null);
        return $this->transaction(function () use ($data) {
            $type = $this->getTransactionType('OUTCOME');
            $this->lockCashBoxes([$data['cash_box_id'] ?? null]);

            $spending = Spending::create([
                'tenant_id' => $data['tenant_id'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'cash_box_id' => $data['cash_box_id'] ?? null,
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

            $transaction = Transaction::create([
                'tenant_id' => $spending->tenant_id,
                'company_id' => $spending->company_id,
                'sum' => $spending->sum,
                'cash_box_id' => $spending->cash_box_id,
                'transaction_type_id' => $type->id,
                'payment_method_id' => $data['payment_method_id'] ?? null,
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

            event(new FinancialActionLogged('spending.created', ['spending_id' => $spending->id]));

            return SpendingDTO::fromModel($spending);
        });
    }

    public function createDirectorWithdrawal(array $data): SpendingDTO
    {
        $this->assertPositiveSum($data['sum'] ?? 0);
        $this->assertCashBox($data['cash_box_id'] ?? null);
        return $this->transaction(function () use ($data) {
            $type = $this->getTransactionType('DIRECTOR_WITHDRAWAL');
            $this->lockCashBoxes([$data['cash_box_id'] ?? null]);

            $spending = Spending::create([
                'tenant_id' => $data['tenant_id'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'cash_box_id' => $data['cash_box_id'] ?? null,
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

            $transaction = Transaction::create([
                'tenant_id' => $spending->tenant_id,
                'company_id' => $spending->company_id,
                'sum' => $spending->sum,
                'cash_box_id' => $spending->cash_box_id,
                'transaction_type_id' => $type->id,
                'payment_method_id' => $data['payment_method_id'] ?? null,
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

            event(new FinancialActionLogged('director_withdrawal.created', ['spending_id' => $spending->id]));

            return SpendingDTO::fromModel($spending);
        });
    }

    public function transferBetweenCashBoxes(array $data): CashTransferDTO
    {
        $this->assertPositiveSum($data['sum'] ?? 0);
        $this->assertTransferBoxes($data['from_cash_box_id'] ?? null, $data['to_cash_box_id'] ?? null);
        return $this->transaction(function () use ($data) {
            $typeOut = $this->getTransactionType('TRANSFER_OUT');
            $typeIn = $this->getTransactionType('TRANSFER_IN');

            $this->assertSameContext($data['from_cash_box_id'] ?? null, $data['to_cash_box_id'] ?? null);
            $this->lockCashBoxes([$data['from_cash_box_id'] ?? null, $data['to_cash_box_id'] ?? null]);

            $txOut = Transaction::create([
                'tenant_id' => $data['tenant_id'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'sum' => $data['sum'] ?? 0,
                'cash_box_id' => $data['from_cash_box_id'] ?? null,
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
                'cash_box_id' => $data['to_cash_box_id'] ?? null,
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
                'from_cash_box_id' => $data['from_cash_box_id'] ?? null,
                'to_cash_box_id' => $data['to_cash_box_id'] ?? null,
                'sum' => $data['sum'] ?? 0,
                'description' => $data['description'] ?? null,
                'transaction_out_id' => $txOut->id,
                'transaction_in_id' => $txIn->id,
                'created_by' => $data['created_by_user_id'] ?? null,
                'created_at' => $data['date'] ?? now(),
            ]);

            event(new FinancialActionLogged('cash_transfer.created', ['cash_transfer_id' => $transfer->id]));

            return CashTransferDTO::fromModel($transfer);
        });
    }

    public function completeTransaction(Transaction $transaction): TransactionDTO
    {
        if ($transaction->is_completed) {
            throw new RuntimeException('Transaction already completed');
        }

        $this->lockCashBoxes([$transaction->cash_box_id]);

        $type = $this->getTransactionTypeById((int) $transaction->transaction_type_id);

        $currentBalance = $this->getCashBoxBalance((int) $transaction->cash_box_id);
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
            'cashbox_id' => $transaction->cash_box_id,
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
            ->where('t.cash_box_id', $cashBoxId)
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

    private function assertCashBox($cashBoxId): void
    {
        if (!$cashBoxId || !CashBox::query()->find($cashBoxId)) {
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

    private function assertSameContext($fromCashBoxId, $toCashBoxId): void
    {
        $from = CashBox::query()->find($fromCashBoxId);
        $to = CashBox::query()->find($toCashBoxId);

        if (!$from || !$to) {
            throw new RuntimeException('Cash boxes not found');
        }

        if ($from->tenant_id !== $to->tenant_id) {
            throw new RuntimeException('Cash boxes belong to different tenants');
        }

        if ($from->company_id !== null && $to->company_id !== null && $from->company_id !== $to->company_id) {
            throw new RuntimeException('Cash boxes belong to different companies');
        }
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
            ->table('cash_boxes')
            ->whereIn('id', $ids->all())
            ->lockForUpdate()
            ->get();
    }
}
