<?php

namespace App\Services\Finance;

use App\Domain\Finance\Enums\ContractSystemStatusEnum;
use App\Repositories\Finance\ContractRepository;
use App\Repositories\Finance\ReceiptRepository;
use App\Repositories\Finance\TransactionRepository;
use Illuminate\Support\Facades\DB;
use App\Events\ContractRecalculated;

class ContractFinanceService
{
    public function __construct(
        private readonly ContractRepository $contracts,
        private readonly TransactionRepository $transactions,
        private readonly ReceiptRepository $receipts,
    ) {
    }

    public function recalc(int $contractId): void
    {
        DB::transaction(function () use ($contractId) {
            $this->updatePaidAmount($contractId);
            $this->updateContractStatus($contractId);
            event(new ContractRecalculated($contractId));
        });
    }

    public function recalcAll(): void
    {
        $contracts = $this->contracts->getActiveContracts();

        foreach ($contracts as $contract) {
            $this->recalc($contract->id);
        }
    }

    public function updatePaidAmount(int $contractId): void
    {
        $paid = $this->receipts->sumByContract($contractId);
        $this->contracts->updatePaidAmount($contractId, $paid);
    }

    public function updateContractStatus(int $contractId): void
    {
        $contract = $this->contracts->findWithRelations($contractId);
        if (!$contract) {
            return;
        }

        $isCompleted = $contract->paid_amount >= $contract->total_amount;
        $status = $this->calculateStatus($contract->paid_amount, $contract->total_amount);

        $this->contracts->updateCompletedStatus($contractId, $isCompleted);
        $this->contracts->updateSystemStatus($contractId, $status->code());
    }

    public function handleOverpayment(int $contractId): void
    {
        // TODO: обработка переплаты.
    }

    public function handleUnderpayment(int $contractId): void
    {
        // TODO: обработка недоплаты.
    }

    public function handlePartialPayment(int $contractId): void
    {
        // TODO: обработка частичной оплаты.
    }

    public function linkPayment(int $contractId, int $paymentId): void
    {
        // TODO: привязка платежа к контракту.
    }

    public function unlinkPayment(int $contractId, int $paymentId): void
    {
        // TODO: отвязка платежа от контракта.
    }

    public function attachTransaction(int $contractId, int $transactionId): void
    {
        // TODO: привязка транзакции к контракту.
    }

    public function updateWorkStatus(int $contractId): void
    {
        // TODO: обновление статуса выполнения работ.
    }

    private function calculateStatus(float $paid, ?float $total): ContractSystemStatusEnum
    {
        $totalAmount = (float) ($total ?? 0);

        if ($paid <= 0) {
            return ContractSystemStatusEnum::NOT_PAID;
        }

        if ($totalAmount <= 0) {
            return ContractSystemStatusEnum::OVERPAID;
        }

        if ($paid > 0 && $paid < $totalAmount) {
            return ContractSystemStatusEnum::PARTIALLY_PAID;
        }

        if ($paid == $totalAmount) {
            return ContractSystemStatusEnum::FULLY_PAID;
        }

        return ContractSystemStatusEnum::OVERPAID;
    }
}
