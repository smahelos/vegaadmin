<?php

declare(strict_types=1);

namespace App\Domain\User\DTO;

use App\Domain\User\Enums\LimitedEntity;

/**
 * The owner's usage of one plan limit: a null limit is unlimited, 0 not allowed.
 */
final readonly class EntityUsageDTO
{
    public function __construct(
        public LimitedEntity $entity,
        public int $used,
        public ?int $limit,
    ) {}

    /**
     * Share of a numeric limit used, capped at 100; 0 for unlimited and not allowed.
     */
    public function percent(): int
    {
        if ($this->limit === null || $this->limit === 0) {
            return 0;
        }

        return min(
            100,
            intdiv($this->used * 100, $this->limit),
        );
    }

    // A limit of 0 allows nothing, so it is full from the start
    public function isFull(): bool
    {
        return $this->limit !== null && $this->used >= $this->limit;
    }

    // Warned from 80 % until the limit is reached
    public function isNear(): bool
    {
        return !$this->isFull() && $this->percent() >= 80;
    }
}
