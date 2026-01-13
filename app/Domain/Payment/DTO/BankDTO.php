<?php

namespace App\Domain\Payment\DTO;

class BankDTO
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly ?string $code,
        public readonly ?string $swift,
        public readonly ?string $country,
        public readonly bool $active,
        public readonly ?string $description,
        public readonly ?string $created_at,
    ) {}

    /** Array-based constructor for mappers. */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: $data['name'] ?? null,
            code: $data['code'] ?? null,
            swift: $data['swift'] ?? null,
            country: $data['country'] ?? null,
            active: (bool) ($data['active'] ?? false),
            description: $data['description'] ?? null,
            created_at: $data['created_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'swift' => $this->swift,
            'country' => $this->country,
            'active' => $this->active,
            'description' => $this->description,
            'created_at' => $this->created_at,
        ];
    }
}
