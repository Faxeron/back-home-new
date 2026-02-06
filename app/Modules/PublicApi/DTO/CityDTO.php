<?php

namespace App\Modules\PublicApi\DTO;

final class CityDTO
{
    public function __construct(
        public string $slug,
        public string $name,
        public ?int $company_id,
    ) {
    }

    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'company_id' => $this->company_id,
        ];
    }
}
