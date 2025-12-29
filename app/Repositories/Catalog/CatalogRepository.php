<?php

namespace App\Repositories\Catalog;

use App\Domain\Catalog\DTO\BaseFilterDTO;
use App\Domain\Catalog\DTO\ProductFilterDTO;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBrand;
use App\Domain\Catalog\Models\ProductCategory;
use App\Domain\Catalog\Models\ProductKind;
use App\Domain\Catalog\Models\ProductSubcategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CatalogRepository
{
    public function paginateProducts(ProductFilterDTO $filter): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['category', 'subCategory', 'brand', 'kind']);

        if ($filter->tenantId) {
            $query->where('tenant_id', $filter->tenantId);
        }

        if ($filter->companyId) {
            $query->where(function ($builder) use ($filter) {
                $builder->whereNull('company_id')
                    ->orWhere('company_id', $filter->companyId);
            });
        }

        if ($filter->search) {
            $query->where(function ($builder) use ($filter) {
                $builder->where('name', 'like', "%{$filter->search}%")
                    ->orWhere('scu', 'like', "%{$filter->search}%");
            });
        }

        if ($filter->categoryId) {
            $query->where('category_id', $filter->categoryId);
        }

        if ($filter->subCategoryId) {
            $query->where('sub_category_id', $filter->subCategoryId);
        }

        if ($filter->brandId) {
            $query->where('brand_id', $filter->brandId);
        }

        return $query
            ->orderBy('sort_order')
            ->orderBy('scu')
            ->paginate($filter->perPage, ['*'], 'page', $filter->page);
    }

    public function paginateCategories(BaseFilterDTO $filter): LengthAwarePaginator
    {
        $query = ProductCategory::query()
            ->withCount(['products'])
            ->orderBy('name');

        if ($filter->tenantId) {
            $query->where('tenant_id', $filter->tenantId);
        }

        if ($filter->companyId) {
            $query->where(function ($builder) use ($filter) {
                $builder->whereNull('company_id')
                    ->orWhere('company_id', $filter->companyId);
            });
        }

        if ($filter->search) {
            $query->where('name', 'like', "%{$filter->search}%");
        }

        return $query->paginate($filter->perPage, ['*'], 'page', $filter->page);
    }

    public function paginateSubcategories(BaseFilterDTO $filter, ?int $categoryId = null): LengthAwarePaginator
    {
        $query = ProductSubcategory::query()
            ->with(['category'])
            ->withCount(['products'])
            ->orderBy('name');

        if ($filter->tenantId) {
            $query->where('tenant_id', $filter->tenantId);
        }

        if ($filter->companyId) {
            $query->where(function ($builder) use ($filter) {
                $builder->whereNull('company_id')
                    ->orWhere('company_id', $filter->companyId);
            });
        }

        if ($filter->search) {
            $query->where('name', 'like', "%{$filter->search}%");
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return $query->paginate($filter->perPage, ['*'], 'page', $filter->page);
    }

    public function paginateBrands(BaseFilterDTO $filter): LengthAwarePaginator
    {
        $query = ProductBrand::query()
            ->withCount(['products'])
            ->orderBy('name');

        if ($filter->tenantId) {
            $query->where('tenant_id', $filter->tenantId);
        }

        if ($filter->companyId) {
            $query->where(function ($builder) use ($filter) {
                $builder->whereNull('company_id')
                    ->orWhere('company_id', $filter->companyId);
            });
        }

        if ($filter->search) {
            $query->where('name', 'like', "%{$filter->search}%");
        }

        return $query->paginate($filter->perPage, ['*'], 'page', $filter->page);
    }

    public function paginateKinds(BaseFilterDTO $filter): LengthAwarePaginator
    {
        $query = ProductKind::query()
            ->orderBy('name');

        if ($filter->tenantId) {
            $query->where('tenant_id', $filter->tenantId);
        }

        if ($filter->companyId) {
            $query->where(function ($builder) use ($filter) {
                $builder->whereNull('company_id')
                    ->orWhere('company_id', $filter->companyId);
            });
        }

        if ($filter->search) {
            $query->where('name', 'like', "%{$filter->search}%");
        }

        return $query->paginate($filter->perPage, ['*'], 'page', $filter->page);
    }
}
