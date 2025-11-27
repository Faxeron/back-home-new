<?php

namespace App\Domain\Finance\DTO;

use Illuminate\Http\Request;

class BaseFilterDTO
{
    public function __construct(
        public ?int $tenantId = null,
        public ?int $companyId = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?string $search = null,
        public ?string $sort = null,
        public ?string $direction = null,
        public int $perPage = 25,
        public int $page = 1,
    ) {
    }

    public static function fromRequest(Request $request, ?int $tenantId): static
    {
        return new static(
            tenantId: $tenantId,
            companyId: $request->integer('company_id') ?: null,
            dateFrom: $request->date('date_from')?->toDateString(),
            dateTo: $request->date('date_to')?->toDateString(),
            search: $request->string('search')->toString() ?: null,
            sort: $request->string('sort')->toString() ?: null,
            direction: $request->string('direction')->toString() ?: null,
            perPage: max(1, min(100, (int) $request->integer('per_page', 25))),
            page: max(1, (int) $request->integer('page', 1)),
        );
    }
}
