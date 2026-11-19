<?php

declare(strict_types=1);

// Stands for application code: the rule test checks this directory like app/
namespace App\PermissionsRead;

use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\User\Enums\Permission;
use App\Models\Business;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

final class ReadsPermissions
{
    public function user(User $user, ?User $admin): void
    {
        $user->can(Permission::CONTACT_VIEW);
        $user->hasPermissionTo(Permission::CONTACT_VIEW);
        $user->hasRole('admin');
        $admin?->getAllPermissions();
    }

    public function uncertain(
        mixed $entry,
        Authenticatable $admin,
        User|Business $party,
    ): void {
        $entry->hasAnyRole(['admin']);
        $admin->can('contact.view');
        $party->getRoleNames();
    }

    public function gate(AuthorizingUseCaseRunnerInterface $useCases, Actor $actor): void
    {
        $useCases->holds($actor, Permission::CONTACT_VIEW);
    }
}
