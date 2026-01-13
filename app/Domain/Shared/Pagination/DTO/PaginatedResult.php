<?php

namespace App\Domain\Shared\Pagination\DTO;

/**
 * Domain-agnostic pagination result DTO.
 * @template T
 */
class PaginatedResult
{
    /**
     * @param array<int, mixed> $items
     */
    public function __construct(
        private readonly array $items,
        private readonly int $total,
        private readonly int $perPage,
        private readonly int $currentPage,
        private readonly int $lastPage,
        private readonly bool $hasMore
    ) {
    }

    /**
     * @return array<int, mixed>
     */
    public function items(): array { return $this->items; }
    public function total(): int { return $this->total; }
    public function perPage(): int { return $this->perPage; }
    public function currentPage(): int { return $this->currentPage; }
    public function lastPage(): int { return $this->lastPage; }
    public function hasMore(): bool { return $this->hasMore; }
}
