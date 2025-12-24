<?php

namespace App\Domain\Finance\DTO;

use App\Domain\Finance\Models\Transaction;

class TransactionDTO
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $tenantId,
        public readonly ?int $companyId,
        public readonly float $sum,
        public readonly ?int $cashBoxId,
        public readonly int $transactionTypeId,
        public readonly ?int $paymentMethodId,
        public readonly ?int $counterpartyId,
        public readonly ?int $contractId,
        public readonly ?int $relatedId,
        public readonly bool $isPaid,
        public readonly bool $isCompleted,
    ) {
    }

    public static function fromModel(Transaction $transaction): self
    {
        return new self(
            id: $transaction->id,
            tenantId: $transaction->tenant_id,
            companyId: $transaction->company_id,
            sum: (float) $transaction->sum,
            cashBoxId: $transaction->cashbox_id,
            transactionTypeId: (int) $transaction->transaction_type_id,
            paymentMethodId: $transaction->payment_method_id,
            counterpartyId: $transaction->counterparty_id,
            contractId: $transaction->contract_id,
            relatedId: $transaction->related_id,
            isPaid: (bool) $transaction->is_paid,
            isCompleted: (bool) $transaction->is_completed,
        );
    }
}
