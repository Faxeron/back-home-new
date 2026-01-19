<?php

namespace App\Domain\Finance\DTO;

use App\Domain\Finance\Models\Receipt;
use App\Domain\Finance\ValueObjects\Money;

class ReceiptDTO
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $tenantId,
        public readonly ?int $companyId,
        public readonly ?int $cashBoxId,
        public readonly ?int $transactionId,
        public readonly float $sum,
        public readonly ?int $contractId,
        public readonly ?int $counterpartyId,
        public readonly ?string $paymentDate,
        public readonly ?string $description,
    ) {
    }

    public static function fromModel(Receipt $receipt): self
    {
        $sum = $receipt->sum;
        $sumValue = $sum instanceof Money ? $sum->toFloat() : (float) $sum;

        return new self(
            id: $receipt->id,
            tenantId: $receipt->tenant_id,
            companyId: $receipt->company_id,
            cashBoxId: $receipt->cashbox_id,
            transactionId: $receipt->transaction_id,
            sum: $sumValue,
            contractId: $receipt->contract_id,
            counterpartyId: $receipt->counterparty_id,
            paymentDate: $receipt->payment_date?->toDateString(),
            description: $receipt->description,
        );
    }
}
