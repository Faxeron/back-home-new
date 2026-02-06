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

    public function paginateProducts(PublicProductFilterDTO $filter, ?int $resolvedCompanyId = null): LengthAwarePaginator
    {
        $companyId = $resolvedCompanyId ?? $filter->company_id;

        $query = Product::query()
            ->with(['category', 'brand', 'media'])
            ->where('tenant_id', self::TENANT_ID)
            ->whereNull('archived_at')
            ->where('is_visible', true);

        if ($companyId) {
            $query->where(function ($builder) use ($companyId) {
                $builder->where('company_id', $companyId)
                    ->orWhere('is_global', true);
            });
        }

        if ($filter->category) {
            if (ctype_digit($filter->category)) {
                $query->where('category_id', (int) $filter->category);
            } else {
                $query->whereHas('category', function ($builder) use ($filter) {
                    $builder->where('name', 'like', $filter->category);
                });
            }
        }

        return $query
            ->orderBy('sort_order')
            ->orderBy('name')
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
            ->addSelect([
                'pcp_price' => 'pcp.price',
                'pcp_price_sale' => 'pcp.price_sale',
                'pcp_price_delivery' => 'pcp.price_delivery',
                'pcp_montaj' => 'pcp.montaj',
                'pcp_currency' => 'pcp.currency',
                'pcp_company_id' => 'pcp.company_id',
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
