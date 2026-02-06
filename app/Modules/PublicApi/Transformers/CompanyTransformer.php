<?php

namespace App\Modules\PublicApi\Transformers;

use App\Domain\Common\Models\Company;
use App\Modules\PublicApi\DTO\CompanyDTO;

final class CompanyTransformer
{
    public function toDTO(Company $company): CompanyDTO
    {
        return new CompanyDTO(
            id: (int) $company->id,
            name: (string) $company->name,
            phone: $company->phone ? (string) $company->phone : null,
            address: $company->address ? (string) $company->address : null,
            email: $company->email ? (string) $company->email : null,
        );
    }
}
