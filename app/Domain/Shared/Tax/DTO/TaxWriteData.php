<?php

namespace App\Domain\Shared\Tax\DTO;

class TaxWriteData
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly float $rate,
        public readonly ?string $description = null,
        public readonly bool $is_active = true,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'rate' => $this->rate,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ];
    }
}
