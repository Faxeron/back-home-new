<?php

namespace App\Domain\Finance\DTO;

final class TransactionData
{
    public function __construct(
        public readonly float $sum,
        public readonly int $transactionTypeId,
        public readonly int $companyId,
        public readonly ?int $cashBoxId = null,
        public readonly ?int $contractId = null,
        public readonly ?int $counterpartyId = null,
        public readonly ?int $paymentMethodId = null,
        public readonly ?int $cashflowItemId = null,
        public readonly ?string $notes = null,
        public readonly ?bool $isPaid = null,
        public readonly ?bool $isCompleted = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            sum: (float) ($data['sum'] ?? 0),
            transactionTypeId: (int) ($data['transaction_type_id'] ?? 0),
            companyId: (int) ($data['company_id'] ?? 0),
            cashBoxId: isset($data['cashbox_id']) ? (int) $data['cashbox_id'] : null,
            contractId: isset($data['contract_id']) ? (int) $data['contract_id'] : null,
            counterpartyId: isset($data['counterparty_id']) ? (int) $data['counterparty_id'] : null,
            paymentMethodId: isset($data['payment_method_id']) ? (int) $data['payment_method_id'] : null,
            cashflowItemId: isset($data['cashflow_item_id']) ? (int) $data['cashflow_item_id'] : null,
            notes: $data['notes'] ?? null,
            isPaid: $data['is_paid'] ?? null,
            isCompleted: $data['is_completed'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'sum' => $this->sum,
            'transaction_type_id' => $this->transactionTypeId,
            'company_id' => $this->companyId,
            'cashbox_id' => $this->cashBoxId,
            'contract_id' => $this->contractId,
            'counterparty_id' => $this->counterpartyId,
            'payment_method_id' => $this->paymentMethodId,
            'cashflow_item_id' => $this->cashflowItemId,
            'notes' => $this->notes,
            'is_paid' => $this->isPaid,
            'is_completed' => $this->isCompleted,
        ];
    }
}
