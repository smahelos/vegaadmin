<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Domain\User\Enums\LimitedEntity;
use DomainException;

/**
 * Creating or restoring a record would exceed the limit of its owner's plan role.
 */
final class EntityLimitExceededException extends DomainException
{
    public function __construct(
        public readonly LimitedEntity $entity,
        public readonly int $limit,
    ) {
        parent::__construct("The {$entity->value} limit of {$limit} is reached");
    }
}
