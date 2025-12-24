<?php

namespace App\Domain\Finance\DTO;

use Illuminate\Http\Request;

class ReceiptFilterDTO extends BaseFilterDTO
{
    public function __construct(
        ?int $tenantId,
        ?int $companyId = null,
        public ?int $cashBoxId = null,
        public ?int $contractId = null,
        public ?int $counterpartyId = null,
        public ?float $sumMin = null,
        public ?float $sumMax = null,
        public ?string $paymentDateFrom = null,
        public ?string $paymentDateTo = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $search = null,
        ?string $sort = null,
        ?string $direction = null,
        int $perPage = 25,
        int $page = 1,
    ) {
        parent::__construct($tenantId, $companyId, $dateFrom, $dateTo, $search, $sort, $direction, $perPage, $page);
    }

    public static function fromRequest(Request $request, ?int $tenantId): static
    {
        $base = parent::fromRequest($request, $tenantId);

        return new static(
            tenantId: $base->tenantId,
            companyId: $base->companyId,
            cashBoxId: $request->integer('cashbox_id') ?: null,
            contractId: $request->integer('contract_id') ?: null,
            counterpartyId: $request->integer('counterparty_id') ?: null,
            sumMin: $request->has('sum_min') ? (float) $request->input('sum_min') : null,
            sumMax: $request->has('sum_max') ? (float) $request->input('sum_max') : null,
            paymentDateFrom: $request->date('payment_date_from')?->toDateString(),
            paymentDateTo: $request->date('payment_date_to')?->toDateString(),
            dateFrom: $base->dateFrom,
            dateTo: $base->dateTo,
            search: $base->search,
            sort: $base->sort,
            direction: $base->direction,
            perPage: $base->perPage,
            page: $base->page,
        );
    }
}
