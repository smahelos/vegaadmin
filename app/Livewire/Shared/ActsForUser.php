<?php

declare(strict_types=1);

namespace App\Livewire\Shared;

use App\Domain\User\ValueObjects\UserId;
use Illuminate\Support\Facades\Auth;

/**
 * The signed-in frontend user of a component. Every action checks its permission again, because Livewire calls
 * an action without passing the route middleware of the page.
 */
trait ActsForUser
{
    protected function userId(): UserId
    {
        $id = Auth::id();
        abort_if($id === null, 403);

        return UserId::fromInt((int) $id);
    }

    protected function requirePermission(string $permission): void
    {
        abort_unless($this->userCan($permission), 403);
    }

    protected function userCan(string $permission): bool
    {
        return Auth::user()?->can($permission) === true;
    }
}
