<?php

declare(strict_types=1);

namespace App\Domain\User\Contracts;

use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\ValueObjects\UserId;
use DateTimeImmutable;

/**
 * Counts the owner's records of a limited entity from the entity's own rows.
 */
interface LimitedEntityCounterInterface
{
    /**
     * Records taking a capacity place: not archived businesses and contacts, every product.
     */
    public function countActive(UserId $owner, LimitedEntity $entity): int;

    /**
     * The same count as a locking read, for the transaction that creates or restores a record.
     */
    public function countActiveForUpdate(UserId $owner, LimitedEntity $entity): int;

    /**
     * Records of a monthly quota created at or after the given time.
     */
    public function countCreatedSince(
        UserId $owner,
        LimitedEntity $entity,
        DateTimeImmutable $since,
    ): int;

    /**
     * The same count as a locking read, for the transaction that creates a record.
     */
    public function countCreatedSinceForUpdate(
        UserId $owner,
        LimitedEntity $entity,
        DateTimeImmutable $since,
    ): int;
}
