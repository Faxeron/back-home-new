<?php

namespace App\Domain\Catalog\DTO;

use Illuminate\Http\Request;

class ProductFilterDTO extends BaseFilterDTO
{
    public function __construct(
        ?int $tenantId = null,
        ?int $companyId = null,
        ?string $search = null,
        public ?int $categoryId = null,
        public ?int $subCategoryId = null,
        public ?int $brandId = null,
        int $perPage = 25,
        int $page = 1,
    ) {
        parent::__construct($tenantId, $companyId, $search, $perPage, $page);
    }

    public static function fromRequest(Request $request, ?int $tenantId = null, ?int $companyId = null): static
    {
        $base = parent::fromRequest($request, $tenantId, $companyId);

        return new static(
            tenantId: $base->tenantId,
            companyId: $base->companyId,
            search: $base->search,
            categoryId: $request->integer('category_id') ?: null,
            subCategoryId: $request->integer('sub_category_id') ?: null,
            brandId: $request->integer('brand_id') ?: null,
            perPage: $base->perPage,
            page: $base->page,
        );
    }
}
