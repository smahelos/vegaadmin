<?php

namespace App\Domain\User\DTO;

class UserDTO
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?string $password,
        public readonly ?string $email_verified_at,
        public readonly ?string $created_at,
        public readonly ?string $updated_at,
    ) {}

    /** Array-based constructor for mappers. */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            password: $data['password'] ?? null,
            email_verified_at: $data['email_verified_at'] ?? null,
            created_at: $data['created_at'] ?? null,
            updated_at: $data['updated_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
