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

    /**
     * @return array<string, mixed>|null
     */
    public function getCategoryPage(int $companyId, string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        $category = $this->findCompanyOrGlobal(ProductCategory::class, $companyId, $slug, [
            'id', 'slug', 'name', 'sort_order', 'h1', 'seo_title', 'seo_description',
        ]);

        if (!$category) {
            return null;
        }

        $children = ProductSubcategory::query()
            ->select(['id', 'slug', 'name', 'sort_order'])
            ->where('tenant_id', self::TENANT_ID)
            ->where('is_active', true)
            ->where('category_id', (int) $category->id)
            ->where(function ($q) use ($companyId): void {
                $q->where('company_id', $companyId)->orWhere('is_global', true);
            })
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($s) => [
                'id' => (int) $s->id,
                'slug' => (string) $s->slug,
                'name' => (string) $s->name,
                'sort_order' => (int) ($s->sort_order ?? 0),
            ])
            ->values()
            ->all();

        return [
            'id' => (int) $category->id,
            'slug' => (string) $category->slug,
            'name' => (string) $category->name,
            'sort_order' => (int) ($category->sort_order ?? 0),
            'h1' => $category->h1 ? (string) $category->h1 : null,
            'seo_title' => $category->seo_title ? (string) $category->seo_title : null,
            'seo_description' => $category->seo_description ? (string) $category->seo_description : null,
            'children' => $children,
            'company_id' => $companyId,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSubcategoryPage(int $companyId, string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        $subcategory = $this->findCompanyOrGlobal(ProductSubcategory::class, $companyId, $slug, [
            'id', 'slug', 'name', 'sort_order', 'category_id', 'h1', 'seo_title', 'seo_description',
        ]);

        if (!$subcategory) {
            return null;
        }

        $category = ProductCategory::query()
            ->select(['id', 'slug', 'name', 'sort_order', 'h1', 'seo_title', 'seo_description'])
            ->where('tenant_id', self::TENANT_ID)
            ->where('is_active', true)
            ->where('id', (int) $subcategory->category_id)
            ->where(function ($q) use ($companyId): void {
                $q->where('company_id', $companyId)->orWhere('is_global', true);
            })
            ->first();

        $categoryArr = $category ? [
            'id' => (int) $category->id,
            'slug' => (string) $category->slug,
            'name' => (string) $category->name,
            'sort_order' => (int) ($category->sort_order ?? 0),
            'h1' => $category->h1 ? (string) $category->h1 : null,
            'seo_title' => $category->seo_title ? (string) $category->seo_title : null,
            'seo_description' => $category->seo_description ? (string) $category->seo_description : null,
        ] : null;

        return [
            'id' => (int) $subcategory->id,
            'slug' => (string) $subcategory->slug,
            'name' => (string) $subcategory->name,
            'sort_order' => (int) ($subcategory->sort_order ?? 0),
            'h1' => $subcategory->h1 ? (string) $subcategory->h1 : null,
            'seo_title' => $subcategory->seo_title ? (string) $subcategory->seo_title : null,
            'seo_description' => $subcategory->seo_description ? (string) $subcategory->seo_description : null,
            'category' => $categoryArr,
            'company_id' => $companyId,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getBrandPage(int $companyId, string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        $brand = $this->findCompanyOrGlobal(ProductBrand::class, $companyId, $slug, [
            'id', 'slug', 'name', 'sort_order',
        ]);

        if (!$brand) {
            return null;
        }

        return [
            'id' => (int) $brand->id,
            'slug' => (string) $brand->slug,
            'name' => (string) $brand->name,
            'sort_order' => (int) ($brand->sort_order ?? 0),
            'company_id' => $companyId,
        ];
    }

    /**
     * Prefer company-specific record; fallback to global.
     *
     * @param class-string<\Illuminate\Database\Eloquent\Model> $modelClass
     * @param array<int, string> $select
     */
    private function findCompanyOrGlobal(string $modelClass, int $companyId, string $slug, array $select)
    {
        $base = $modelClass::query()
            ->select($select)
            ->where('tenant_id', self::TENANT_ID)
            ->where('is_active', true)
            ->where('slug', $slug);

        $company = (clone $base)->where('company_id', $companyId)->first();
        if ($company) {
            return $company;
        }

        return $base->where('is_global', true)->first();
    }
}
