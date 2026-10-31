<?php

declare(strict_types=1);

namespace App\Domain\User\DTO;

final readonly class UserWriteData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password = null,
        public ?string $description = null,
        public ?string $street = null,
        public ?string $city = null,
        public ?string $zip = null,
        public ?string $country = null,
        public ?string $phone = null,
        public ?string $created_at = null,
        public ?string $updated_at = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            email: $data['email'] ?? '',
            password: $data['password'] ?? null,
            description: $data['description'] ?? null,
            street: $data['street'] ?? null,
            city: $data['city'] ?? null,
            zip: $data['zip'] ?? null,
            country: $data['country'] ?? null,
            phone: $data['phone'] ?? null,
            created_at: $data['created_at'] ?? null,
            updated_at: $data['updated_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'description' => $this->description,
            'street' => $this->street,
            'city' => $this->city,
            'zip' => $this->zip,
            'country' => $this->country,
            'phone' => $this->phone,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // Only include password if it's not null
        if ($this->password !== null) {
            $data['password'] = $this->password;
        }

        return $data;
    }
}
