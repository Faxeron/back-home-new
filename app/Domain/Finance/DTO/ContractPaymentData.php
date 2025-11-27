<?php

namespace App\Domain\Finance\DTO;

final class ContractPaymentData
{
    public function __construct(
        public readonly int $contractId,
        public readonly float $amount,
        public readonly ?int $receiptId = null,
        public readonly ?int $transactionId = null,
        public readonly ?string $description = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            contractId: (int) ($data['contract_id'] ?? 0),
            amount: (float) ($data['amount'] ?? 0),
            receiptId: isset($data['receipt_id']) ? (int) $data['receipt_id'] : null,
            transactionId: isset($data['transaction_id']) ? (int) $data['transaction_id'] : null,
            description: $data['description'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'contract_id' => $this->contractId,
            'amount' => $this->amount,
            'receipt_id' => $this->receiptId,
            'transaction_id' => $this->transactionId,
            'description' => $this->description,
        ];
    }
}
