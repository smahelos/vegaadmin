<?php

namespace App\Application\Shared\Form\DTO;

/**
 * LimitDTO encapsulates limit information (limit, current usage, allowed)
 * to allow stronger typing versus loose associative arrays.
 */
class LimitDTO
{
    public function __construct(
        public readonly int $limit,
        public readonly int $currentUsage,
        public readonly bool $allowed,
    ) {}

    /**
     * Factory from legacy array shape.
     * @param array{limit:int,current_usage:int,allowed:bool} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['limit'] ?? 0,
            $data['current_usage'] ?? 0,
            $data['allowed'] ?? false,
        );
    }

    /**
     * Export to legacy array shape.
     * @return array{limit:int,current_usage:int,allowed:bool}
     */
    public function toArray(): array
    {
        return [
            'limit' => $this->limit,
            'current_usage' => $this->currentUsage,
            'allowed' => $this->allowed,
        ];
    }
}
