<?php

namespace App\Modules\PublicApi\Services;

use App\Domain\Catalog\Models\Product;
use App\Domain\Common\Models\City;
use App\Modules\PublicApi\DTO\PublicProductFilterDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
            ->with(['category', 'subCategory', 'brand', 'media', 'description']);

        // Legacy category filter.
        if ($filter->category && !$filter->category_id) {
            if (ctype_digit($filter->category)) {
                $query->where('products.category_id', (int) $filter->category);
            } else {
                $query->whereHas('category', function ($builder) use ($filter) {
                    $builder->where('name', 'like', '%' . $filter->category . '%');
                });
            }
        }

        if ($filter->q) {
            $needle = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filter->q) . '%';
            $query->where(function (Builder $q) use ($needle): void {
                $q->where('products.name', 'like', $needle)
                    ->orWhere('products.scu', 'like', $needle);
            });
        }

        if ($filter->category_id) {
            $query->where('products.category_id', $filter->category_id);
        }
        if ($filter->sub_category_id) {
            $query->where('products.sub_category_id', $filter->sub_category_id);
        }
        if ($filter->brand_id) {
            $query->where('products.brand_id', $filter->brand_id);
        }

        if ($filter->price_min !== null) {
            $query->whereRaw('COALESCE(pcp.price_sale, pcp.price) >= ?', [$filter->price_min]);
        }
        if ($filter->price_max !== null) {
            $query->whereRaw('COALESCE(pcp.price_sale, pcp.price) <= ?', [$filter->price_max]);
        }

        foreach ($filter->attrs as $attrId => $value) {
            $query->whereExists(function ($sub) use ($companyId, $attrId, $value): void {
                $sub->select(DB::raw('1'))
                    ->from('product_attribute_values as pav')
                    ->whereColumn('pav.product_id', 'products.id')
                    ->where('pav.tenant_id', self::TENANT_ID)
                    ->where(function ($q) use ($companyId): void {
                        $q->where('pav.company_id', $companyId)->orWhereNull('pav.company_id');
                    })
                    ->where('pav.attribute_id', (int) $attrId)
                    ->where(function ($w) use ($value): void {
                        if (is_array($value)) {
                            $vals = array_values(array_filter(array_map('strval', $value), fn ($v) => trim($v) !== ''));
                            if (empty($vals)) {
                                $w->whereRaw('1=0');
                                return;
                            }

                            $numericVals = array_filter($vals, fn ($v) => is_numeric($v));
                            if (!empty($numericVals)) {
                                $w->whereIn('pav.value_number', array_map('floatval', $numericVals))
                                    ->orWhereIn('pav.value_string', $vals);
                            } else {
                                $w->whereIn('pav.value_string', $vals);
                            }
                            return;
                        }

                        $v = (string) $value;
                        if (is_numeric($v)) {
                            $w->where('pav.value_number', (float) $v)->orWhere('pav.value_string', $v);
                        } else {
                            $w->where('pav.value_string', $v);
                        }
                    });
            });
        }

        return $query
            ->orderBy('products.sort_order')
            ->orderByDesc('products.id')
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
            })
            // Never return products without an active company price row.
            ->whereNotNull('pcp.company_id');
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
