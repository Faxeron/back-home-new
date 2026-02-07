<?php

namespace App\Modules\PublicApi\Services;

use App\Domain\Catalog\Models\ProductAttributeDefinition;
use App\Domain\Catalog\Models\ProductBrand;
use App\Domain\Catalog\Models\ProductCategory;
use App\Domain\Catalog\Models\ProductSubcategory;

final class PublicCatalogService
{
    public const TENANT_ID = 1;

    /**
     * @return array{
     *   categories: array<int, array{id:int,slug:string,name:string,sort_order:int,children:array<int,array{id:int,slug:string,name:string,sort_order:int}>}>,
     *   brands: array<int, array{id:int,slug:string,name:string,sort_order:int}>,
     *   filters: array<int, array{id:int,code:?string,name:string,unit:?string,value_type:string,sort_order:int}>
     * }
     */
    public function getTree(int $companyId): array
    {
        $categories = ProductCategory::query()
            ->select(['id', 'slug', 'name', 'sort_order'])
            ->where('tenant_id', self::TENANT_ID)
            ->where('is_active', true)
            ->where(function ($q) use ($companyId): void {
                $q->where('company_id', $companyId)->orWhere('is_global', true);
            })
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        $categoryIds = $categories->pluck('id')->map(fn ($v) => (int) $v)->values()->all();

        $subcategories = empty($categoryIds)
            ? collect()
            : ProductSubcategory::query()
                ->select(['id', 'slug', 'name', 'sort_order', 'category_id'])
                ->where('tenant_id', self::TENANT_ID)
                ->where('is_active', true)
                ->whereIn('category_id', $categoryIds)
                ->where(function ($q) use ($companyId): void {
                    $q->where('company_id', $companyId)->orWhere('is_global', true);
                })
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->get();

        $subByCategory = $subcategories->groupBy('category_id');

        $categoriesTree = $categories
            ->map(function ($cat) use ($subByCategory) {
                $children = ($subByCategory->get($cat->id) ?? collect())
                    ->map(fn ($sub) => [
                        'id' => (int) $sub->id,
                        'slug' => (string) $sub->slug,
                        'name' => (string) $sub->name,
                        'sort_order' => (int) ($sub->sort_order ?? 0),
                    ])
                    ->values()
                    ->all();

                return [
                    'id' => (int) $cat->id,
                    'slug' => (string) $cat->slug,
                    'name' => (string) $cat->name,
                    'sort_order' => (int) ($cat->sort_order ?? 0),
                    'children' => $children,
                ];
            })
            ->values()
            ->all();

        $brands = ProductBrand::query()
            ->select(['id', 'slug', 'name', 'sort_order'])
            ->where('tenant_id', self::TENANT_ID)
            ->where('is_active', true)
            ->where(function ($q) use ($companyId): void {
                $q->where('company_id', $companyId)->orWhere('is_global', true);
            })
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($b) => [
                'id' => (int) $b->id,
                'slug' => (string) $b->slug,
                'name' => (string) $b->name,
                'sort_order' => (int) ($b->sort_order ?? 0),
            ])
            ->values()
            ->all();

        $filters = ProductAttributeDefinition::query()
            ->select(['id', 'code', 'name', 'unit', 'value_type', 'sort_order'])
            ->where('tenant_id', self::TENANT_ID)
            ->where('is_visible', true)
            ->where('is_filterable', true)
            ->where(function ($q) use ($companyId): void {
                $q->where('company_id', $companyId)->orWhere('is_global', true);
            })
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($a) => [
                'id' => (int) $a->id,
                'code' => $a->code ? (string) $a->code : null,
                'name' => (string) $a->name,
                'unit' => $a->unit ? (string) $a->unit : null,
                'value_type' => (string) $a->value_type,
                'sort_order' => (int) ($a->sort_order ?? 0),
            ])
            ->values()
            ->all();

        return [
            'categories' => $categoriesTree,
            'brands' => $brands,
            'filters' => $filters,
        ];
    }
}

