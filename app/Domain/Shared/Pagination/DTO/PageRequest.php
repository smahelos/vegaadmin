<?php

namespace App\Domain\Shared\Pagination\DTO;

/**
 * Domain pagination request value object (framework-agnostic).
 */
class PageRequest
{
    private int $page;
    private int $perPage;

    public function __construct(int $page = 1, int $perPage = 10)
    {
        // Normalize values to safe bounds
        $this->page = max(1, $page);
        $this->perPage = max(1, $perPage);
    }

    public function page(): int
    {
        return $this->page;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }
}
