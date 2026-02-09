<?php

namespace App\Modules\PublicApi\Services;

use App\Domain\Common\Models\City;
use Illuminate\Support\Collection;

final class PublicCityService
{
    public const TENANT_ID = 1;

    /**
     * @return Collection<int, City>
     */
    public function listCities(?string $search = null, ?int $companyId = null): Collection
    {
        $query = City::query()
            ->select(['id', 'slug', 'name', 'name_prepositional', 'name_genitive', 'company_id'])
            ->where('tenant_id', self::TENANT_ID)
            ->whereNotNull('company_id');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($search) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        return $query->orderBy('name')->get();
    }
}
