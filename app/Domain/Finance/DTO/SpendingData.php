<?php

namespace App\Domain\Finance\DTO;

final class SpendingData
{
    public function __construct(
        public readonly float $sum,
        public readonly string $paymentDate,
        public readonly int $companyId,
        public readonly ?int $cashBoxId = null,
        public readonly ?int $contractId = null,
        public readonly ?int $counterpartyId = null,
        public readonly ?int $fundId = null,
        public readonly ?int $spendingItemId = null,
        public readonly ?int $spentToUserId = null,
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
            counterpartyId: isset($data['counterparty_id']) ? (int) $data['counterparty_id'] : null,
            fundId: isset($data['fond_id']) ? (int) $data['fond_id'] : null,
            spendingItemId: isset($data['spending_item_id']) ? (int) $data['spending_item_id'] : null,
            spentToUserId: isset($data['spent_to_user_id']) ? (int) $data['spent_to_user_id'] : null,
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
            'counterparty_id' => $this->counterpartyId,
            'fond_id' => $this->fundId,
            'spending_item_id' => $this->spendingItemId,
            'spent_to_user_id' => $this->spentToUserId,
            'transaction_id' => $this->transactionId,
            'description' => $this->description,
        ];
    }
}
