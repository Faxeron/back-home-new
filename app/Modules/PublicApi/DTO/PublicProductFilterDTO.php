<?php

namespace App\Modules\PublicApi\DTO;

use Illuminate\Http\Request;

final class PublicProductFilterDTO
{
    public function __construct(
        public ?string $city,
        public ?int $company_id,
        public ?string $category,
        public int $per_page,
        public int $page,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $perPage = max(1, min(100, (int) $request->integer('per_page', 24)));
        $page = max(1, (int) $request->integer('page', 1));

        $city = trim((string) $request->get('city', ''));
        $city = $city === '' ? null : $city;

        $companyId = $request->integer('company_id');
        $companyId = $companyId > 0 ? $companyId : null;

        $category = trim((string) $request->get('category', ''));
        $category = $category === '' ? null : $category;

        return new self(
            city: $city,
            company_id: $companyId,
            category: $category,
            per_page: $perPage,
            page: $page,
        );
    }
}
