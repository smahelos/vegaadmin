<?php

namespace App\Domain\Party\DTO;

class ClientDTO
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $street,
        public readonly ?string $city,
        public readonly ?string $zip,
        public readonly ?string $country,
        public readonly ?string $ico,
        public readonly ?string $dic,
        public readonly ?string $shortcut,
        public readonly ?string $description,
        public readonly ?string $created_at,
        public readonly ?int $user_id,
        public readonly bool $is_default,
        /** @var ClientDTO[] */
        public readonly ?array $invoices = [],
    ) {}

    /** Array-based constructor for mappers. */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            street: $data['street'] ?? null,
            city: $data['city'] ?? null,
            zip: $data['zip'] ?? null,
            country: $data['country'] ?? null,
            ico: $data['ico'] ?? null,
            dic: $data['dic'] ?? null,
            shortcut: $data['shortcut'] ?? null,
            description: $data['description'] ?? null,
            created_at: $data['created_at'] ?? null,
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,
            is_default: (bool) ($data['is_default'] ?? false),
            invoices: $data['invoices'] ?? [],
        );
    }
}
