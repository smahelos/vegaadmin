<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Shared\UseCase\Actor;
use App\Domain\User\ValueObjects\UserId;

/**
 * The signed-in admin as the actor of the use cases an admin controller runs.
 */
trait ActsAsAdmin
{
    private function admin(): Actor
    {
        $guard = backpack_auth();

        return Actor::admin(
            UserId::fromInt((int) $guard->id()),
        );
    }
}
