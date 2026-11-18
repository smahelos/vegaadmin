<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\User\Repositories;

use App\Domain\User\Contracts\RoleLimitReadRepositoryInterface;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Authorization\Services\GrantStore;

/**
 * The limits of the request's grants (D-049): read once per request, not under the owner's lock.
 */
final class EloquentRoleLimitRepository implements RoleLimitReadRepositoryInterface
{
    public function __construct(
        private readonly GrantStore $grants,
    ) {}

    public function listForUser(UserId $user): array
    {
        return $this->grants->limits($user, 'web');
    }
}
