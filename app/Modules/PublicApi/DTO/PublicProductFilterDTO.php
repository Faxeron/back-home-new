<?php

namespace App\Modules\PublicApi\DTO;

use Illuminate\Http\Request;

final class PublicProductFilterDTO
{
    public function __construct(
        public ?string $city,
        public ?int $company_id,
        // Legacy param; prefer category_id.
        public ?string $category,
        public ?int $category_id,
        public ?int $sub_category_id,
        public ?int $brand_id,
        public ?float $price_min,
        public ?float $price_max,
        public ?string $q,
        /** @var array<int, string|array<int, string>> */
        public array $attrs,
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

        $categoryId = $request->integer('category_id');
        $categoryId = $categoryId > 0 ? $categoryId : null;

        $subCategoryId = $request->integer('sub_category_id');
        $subCategoryId = $subCategoryId > 0 ? $subCategoryId : null;

        $brandId = $request->integer('brand_id');
        $brandId = $brandId > 0 ? $brandId : null;

        $priceMinRaw = $request->get('price_min');
        $priceMaxRaw = $request->get('price_max');
        $priceMin = $priceMinRaw !== null && $priceMinRaw !== '' ? (float) $priceMinRaw : null;
        $priceMax = $priceMaxRaw !== null && $priceMaxRaw !== '' ? (float) $priceMaxRaw : null;

        $q = trim((string) $request->get('q', ''));
        $q = $q === '' ? null : $q;

        $attrsRaw = $request->input('attrs', []);
        $attrs = [];
        if (is_array($attrsRaw)) {
            foreach ($attrsRaw as $attrId => $value) {
                if (!is_numeric($attrId)) {
                    continue;
                }
                $id = (int) $attrId;
                if ($id <= 0) {
                    continue;
                }

                if (is_array($value)) {
                    $vals = [];
                    foreach ($value as $v) {
                        $v = trim((string) $v);
                        if ($v !== '') {
                            $vals[] = $v;
                        }
                    }
                    if (!empty($vals)) {
                        $attrs[$id] = $vals;
                    }
                } else {
                    $v = trim((string) $value);
                    if ($v !== '') {
                        $attrs[$id] = $v;
                    }
                }
            }
        }

        return new self(
            city: $city,
            company_id: $companyId,
            category: $category,
            category_id: $categoryId,
            sub_category_id: $subCategoryId,
            brand_id: $brandId,
            price_min: $priceMin,
            price_max: $priceMax,
            q: $q,
            attrs: $attrs,
            per_page: $perPage,
            page: $page,
        );
    }
}
