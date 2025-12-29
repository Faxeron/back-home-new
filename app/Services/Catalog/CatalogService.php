<?php

namespace App\Services\Catalog;

use App\Domain\Catalog\DTO\BaseFilterDTO;
use App\Domain\Catalog\DTO\ProductFilterDTO;
use App\Repositories\Catalog\CatalogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CatalogService
{
    public function __construct(private readonly CatalogRepository $catalogRepository)
    {
    }

    public function paginateProducts(ProductFilterDTO $filter): LengthAwarePaginator
    {
        return $this->catalogRepository->paginateProducts($filter);
    }

    public function paginateCategories(BaseFilterDTO $filter): LengthAwarePaginator
    {
        return $this->catalogRepository->paginateCategories($filter);
    }

    public function paginateSubcategories(BaseFilterDTO $filter, ?int $categoryId = null): LengthAwarePaginator
    {
        return $this->catalogRepository->paginateSubcategories($filter, $categoryId);
    }

    public function paginateBrands(BaseFilterDTO $filter): LengthAwarePaginator
    {
        return $this->catalogRepository->paginateBrands($filter);
    }

    public function paginateKinds(BaseFilterDTO $filter): LengthAwarePaginator
    {
        return $this->catalogRepository->paginateKinds($filter);
    }
}
