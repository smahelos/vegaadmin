<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\User\Enums\Permission;
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

    /**
     * For setup(), which hides the operations the admin cannot run; nobody signed in holds nothing.
     */
    private function adminHolds(Permission $permission): bool
    {
        $guard = backpack_auth();
        if ($guard->id() === null) {
            return false;
        }
        $useCases = app(AuthorizingUseCaseRunnerInterface::class);

        return $useCases->holds(
            $this->admin(),
            $permission,
        );
    }
}
