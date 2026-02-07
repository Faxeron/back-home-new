<?php

namespace App\Modules\PublicApi\Services;

use App\Domain\Catalog\Models\Product;
use App\Domain\Common\Models\City;
use App\Modules\PublicApi\DTO\PublicProductFilterDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class PublicProductService
{
    public const TENANT_ID = 1;

    public function findBySlug(string $slug, int $companyId): ?Product
    {
        return $this->baseCompanyQuery($companyId)
            ->with(['category', 'brand', 'media', 'description', 'attributeValues.definition'])
            ->where('slug', $slug)
            ->first();
    }

    public function paginateProducts(PublicProductFilterDTO $filter, int $companyId): LengthAwarePaginator
    {
        $query = $this->baseCompanyQuery($companyId)
            ->with(['category', 'brand', 'media']);

        if ($filter->category) {
            if (ctype_digit($filter->category)) {
                $query->where('products.category_id', (int) $filter->category);
            } else {
                $query->whereHas('category', function ($builder) use ($filter) {
                    $builder->where('name', 'like', '%' . $filter->category . '%');
                });
            }
        }

        return $query
            ->orderBy('products.sort_order')
            ->orderBy('products.name')
            ->paginate($filter->per_page, ['*'], 'page', $filter->page);
    }

    private function baseCompanyQuery(int $companyId): Builder
    {
        return Product::query()
            ->select('products.*')
            ->leftJoin('product_company_prices as pcp', function ($join) use ($companyId): void {
                $join->on('pcp.product_id', '=', 'products.id')
                    ->where('pcp.tenant_id', self::TENANT_ID)
                    ->where('pcp.company_id', $companyId)
                    ->where('pcp.is_active', true);
            })
            // Use explicit aliases; array-key aliasing is not reliable across Builder/Eloquent versions.
            ->addSelect([
                'pcp.price as pcp_price',
                'pcp.price_sale as pcp_price_sale',
                'pcp.price_delivery as pcp_price_delivery',
                'pcp.montaj as pcp_montaj',
                'pcp.currency as pcp_currency',
                'pcp.company_id as pcp_company_id',
            ])
            ->where('products.tenant_id', self::TENANT_ID)
            ->whereNull('products.archived_at')
            ->where('products.is_visible', true)
            ->where(function ($builder) use ($companyId): void {
                $builder->where('products.company_id', $companyId)
                    ->orWhere('products.is_global', true);
            });
    }

    /**
     * @param array<int, int> $companyIds
     * @return array<int, array<int, string>>
     */
    public function getCityMapForCompanyIds(array $companyIds): array
    {
        $companyIds = array_values(array_unique(array_filter($companyIds)));
        if (empty($companyIds)) {
            return [];
        }

        $cities = City::query()
            ->select(['company_id', 'slug'])
            ->where('tenant_id', self::TENANT_ID)
            ->whereIn('company_id', $companyIds)
            ->orderBy('name')
            ->get();

        $map = [];
        foreach ($cities as $city) {
            if (!$city->company_id || !$city->slug) {
                continue;
            }
            $companyId = (int) $city->company_id;
            if (!isset($map[$companyId])) {
                $map[$companyId] = [];
            }
            $map[$companyId][] = (string) $city->slug;
        }

        return $map;
    }
}
