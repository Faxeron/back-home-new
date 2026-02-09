<?php

namespace App\Modules\PublicApi\DTO;

final class CityDTO
{
    public function __construct(
        public string $slug,
        public string $name,
        public ?string $name_prepositional,
        public ?string $name_genitive,
        public ?int $company_id,
    ) {
    }

    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'name_prepositional' => $this->name_prepositional,
            'name_genitive' => $this->name_genitive,
            'company_id' => $this->company_id,
        ];
    }
}
