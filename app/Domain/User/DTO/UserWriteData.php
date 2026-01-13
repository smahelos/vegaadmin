<?php

namespace App\Domain\User\DTO;

class UserWriteData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $password = null,
        public readonly ?string $description = null,
        public readonly ?string $street = null,
        public readonly ?string $city = null,
        public readonly ?string $zip = null,
        public readonly ?string $country = null,
        public readonly ?string $phone = null,
        public readonly ?string $ico = null,
        public readonly ?string $dic = null,
        public readonly ?string $created_at = null,
        public readonly ?string $updated_at = null,
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
            ico: $data['ico'] ?? null,
            dic: $data['dic'] ?? null,
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
            'ico' => $this->ico,
            'dic' => $this->dic,
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
