<?php

namespace App\Domain\Payment\DTO;

use App\Domain\Payment\Enums\PaymentMethodCode;

class PaymentMethodWriteData
{
    public function __construct(
        public readonly string $name,
        public readonly PaymentMethodCode $code,
        public readonly ?string $description,
        public readonly bool $is_active = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            code: PaymentMethodCode::from($data['slug']),
            description: $data['description'] ?? null,
            is_active: (bool) ($data['is_active'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->code->value,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ];
    }
}
