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
     * Export to legacy array shape.
     *
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
