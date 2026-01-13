<?php

namespace App\Domain\Payment\DTO;

class PaymentMethodWriteData
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly bool $is_active = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'],
            description: $data['description'] ?? null,
            is_active: (bool) ($data['is_active'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ];
    }
}
