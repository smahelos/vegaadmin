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
}
