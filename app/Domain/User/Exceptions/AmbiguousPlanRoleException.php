<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Domain\User\ValueObjects\UserId;
use DomainException;

/**
 * A user holds several roles with limits. Limits are never merged (D-047), so creating a limited entity is refused
 * until an admin leaves the user one plan role.
 */
final class AmbiguousPlanRoleException extends DomainException
{
    /**
     * @param  list<string>  $roles
     */
    public function __construct(UserId $user, array $roles)
    {
        parent::__construct(
            "User {$user->toInt()} holds several plan roles: " . implode(', ', $roles),
        );
    }
}
