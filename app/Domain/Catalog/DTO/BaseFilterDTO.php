<?php

namespace App\Domain\Catalog\DTO;

use Illuminate\Http\Request;

class BaseFilterDTO
{
    public function __construct(
        public ?int $tenantId = null,
        public ?int $companyId = null,
        public ?string $search = null,
        public int $perPage = 25,
        public int $page = 1,
    ) {
    }

    public static function fromRequest(Request $request, ?int $tenantId = null, ?int $companyId = null): static
    {
        $perPage = max(1, min(100, (int) $request->integer('per_page', 25)));
        $page = max(1, (int) $request->integer('page', 1));

        return new static(
            tenantId: $tenantId,
            companyId: $companyId,
            search: trim((string) $request->get('q', '')),
            perPage: $perPage,
            page: $page,
        );
    }
}
