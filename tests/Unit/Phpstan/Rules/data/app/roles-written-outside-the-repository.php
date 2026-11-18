<?php

declare(strict_types=1);

// Stands for application code: the rule test checks this directory like app/
namespace App\RolesWritten;

use App\Domain\User\Contracts\UserWriteRepositoryInterface;
use App\Domain\User\ValueObjects\UserId;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

final class WritesRoles
{
    public function user(User $user, Role $role): void
    {
        $user->assignRole($role);
        $roles = $user->roles();
        $roles->sync([]);
        $user->hasRole($role);
    }

    public function role(Role $role): void
    {
        $role->givePermissionTo('contact.view');
        $users = $role->users();
        $users->attach(1);
    }

    public function nullable(?User $user, Role $role): void
    {
        $user->assignRole($role);
        $user?->syncRoles([]);
    }

    public function uncertain(
        mixed $entry,
        Authenticatable $admin,
        User|Business $party,
        UserWriteRepositoryInterface $users,
    ): void {
        $entry->assignRole('admin');
        $entry->roles();
        $admin->syncRoles([]);
        $party->removeRole('admin');
        $users->assignRole(
            UserId::fromInt(1),
            'admin',
        );
    }
}
