<?php

namespace App\Modules\PublicApi\DTO;

final class CompanyDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $phone,
        public ?string $address,
        public ?string $email,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $this->address,
            'email' => $this->email,
        ];
    }
}
