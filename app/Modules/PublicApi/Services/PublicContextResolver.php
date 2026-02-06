<?php

namespace App\Modules\PublicApi\Services;

use App\Domain\Common\Models\City;
use App\Domain\Common\Models\Company;

final class PublicContextResolver
{
    public const TENANT_ID = 1;

    /**
     * @return array{company_id?: int, city?: City, error?: string}
     */
    public function resolve(?string $citySlug, ?int $companyId): array
    {
        $city = null;

        if ($citySlug) {
            $city = City::query()
                ->select(['id', 'slug', 'company_id'])
                ->where('tenant_id', self::TENANT_ID)
                ->where('slug', $citySlug)
                ->whereNotNull('company_id')
                ->first();

            if (!$city || !$city->company_id) {
                return ['error' => 'city_not_found_or_without_company'];
            }

            $cityCompanyId = (int) $city->company_id;
            if ($companyId && $cityCompanyId !== $companyId) {
                return ['error' => 'city_company_mismatch'];
            }

            $companyId = $companyId ?? $cityCompanyId;
        }

        if (!$companyId) {
            return ['error' => 'company_or_city_required'];
        }

        $companyExists = Company::query()
            ->where('tenant_id', self::TENANT_ID)
            ->where('id', $companyId)
            ->where('is_active', true)
            ->exists();

        if (!$companyExists) {
            return ['error' => 'company_not_found'];
        }

        return [
            'company_id' => $companyId,
            'city' => $city,
        ];
    }
}
