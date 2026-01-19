<?php

namespace App\Domain\Finance\DTO;

use App\Domain\Finance\Models\Spending;
use App\Domain\Finance\ValueObjects\Money;

class SpendingDTO
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
        public readonly ?int $fundId,
        public readonly ?int $spendingItemId,
        public readonly ?int $spentToUserId,
        public readonly ?string $paymentDate,
        public readonly ?string $description,
    ) {
    }

    public static function fromModel(Spending $spending): self
    {
        $sum = $spending->sum;
        $sumValue = $sum instanceof Money ? $sum->toFloat() : (float) $sum;

        return new self(
            id: $spending->id,
            tenantId: $spending->tenant_id,
            companyId: $spending->company_id,
            cashBoxId: $spending->cashbox_id,
            transactionId: $spending->transaction_id,
            sum: $sumValue,
            contractId: $spending->contract_id,
            counterpartyId: $spending->counterparty_id,
            fundId: $spending->fond_id,
            spendingItemId: $spending->spending_item_id,
            spentToUserId: $spending->spent_to_user_id,
            paymentDate: $spending->payment_date?->toDateString(),
            description: $spending->description,
        );
    }
}
