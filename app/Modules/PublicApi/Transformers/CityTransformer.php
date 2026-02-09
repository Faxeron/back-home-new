<?php

namespace App\Modules\PublicApi\Transformers;

use App\Domain\Common\Models\City;
use App\Modules\PublicApi\DTO\CityDTO;

final class CityTransformer
{
    public function toDTO(City $city): CityDTO
    {
        return new CityDTO(
            slug: (string) $city->slug,
            name: (string) $city->name,
            name_prepositional: $city->name_prepositional ? (string) $city->name_prepositional : null,
            name_genitive: $city->name_genitive ? (string) $city->name_genitive : null,
            company_id: $city->company_id ? (int) $city->company_id : null,
        );
    }
}
