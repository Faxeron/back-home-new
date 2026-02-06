<?php

namespace App\Modules\PublicApi\Services;

use App\Domain\Common\Models\Company;
use Illuminate\Support\Collection;

final class PublicCompanyService
{
    public const TENANT_ID = 1;

    /**
     * @return Collection<int, Company>
     */
    public function listCompanies(?int $companyId = null): Collection
    {
        $query = Company::query()
            ->select(['id', 'name', 'phone', 'address', 'email'])
            ->where('tenant_id', self::TENANT_ID)
            ->where('is_active', true);

        if ($companyId) {
            $query->where('id', $companyId);
        }

        return $query->orderBy('name')->get();
    }
}
