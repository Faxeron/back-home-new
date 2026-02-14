<?php

namespace App\Domain\Finance\DTO;

final class ReceiptData
{
    public function __construct(
        public readonly float $sum,
        public readonly string $paymentDate,
        public readonly int $companyId,
        public readonly ?int $cashBoxId = null,
        public readonly ?int $contractId = null,
        public readonly ?int $cashflowItemId = null,
        public readonly ?int $financeObjectId = null,
        public readonly ?int $counterpartyId = null,
        public readonly ?int $transactionId = null,
        public readonly ?string $description = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            sum: (float) ($data['sum'] ?? 0),
            paymentDate: (string) ($data['payment_date'] ?? now()->toDateString()),
            companyId: (int) ($data['company_id'] ?? 0),
            cashBoxId: isset($data['cashbox_id']) ? (int) $data['cashbox_id'] : null,
            contractId: isset($data['contract_id']) ? (int) $data['contract_id'] : null,
            cashflowItemId: isset($data['cashflow_item_id']) ? (int) $data['cashflow_item_id'] : null,
            financeObjectId: isset($data['finance_object_id']) ? (int) $data['finance_object_id'] : null,
            counterpartyId: isset($data['counterparty_id']) ? (int) $data['counterparty_id'] : null,
            transactionId: isset($data['transaction_id']) ? (int) $data['transaction_id'] : null,
            description: $data['description'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'sum' => $this->sum,
            'payment_date' => $this->paymentDate,
            'company_id' => $this->companyId,
            'cashbox_id' => $this->cashBoxId,
            'contract_id' => $this->contractId,
            'cashflow_item_id' => $this->cashflowItemId,
            'finance_object_id' => $this->financeObjectId,
            'counterparty_id' => $this->counterpartyId,
            'transaction_id' => $this->transactionId,
            'description' => $this->description,
        ];
    }
}
