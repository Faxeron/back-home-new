<?php

namespace App\Domain\Finance\DTO;

use App\Domain\Finance\Models\CashTransfer;

class CashTransferDTO
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $tenantId,
        public readonly ?int $companyId,
        public readonly int $fromCashBoxId,
        public readonly int $toCashBoxId,
        public readonly float $sum,
        public readonly ?int $transactionOutId,
        public readonly ?int $transactionInId,
        public readonly ?string $description,
    ) {
    }

    public static function fromModel(CashTransfer $transfer): self
    {
        return new self(
            id: $transfer->id,
            tenantId: $transfer->tenant_id,
            companyId: $transfer->company_id,
            fromCashBoxId: (int) $transfer->from_cashbox_id,
            toCashBoxId: (int) $transfer->to_cashbox_id,
            sum: (float) $transfer->sum,
            transactionOutId: $transfer->transaction_out_id,
            transactionInId: $transfer->transaction_in_id,
            description: $transfer->description,
        );
    }
}
