<?php

declare(strict_types=1);

// Application code, which the rule checks; tests are outside its reach
namespace App\RolesWritten;

use App\Models\Role;
use App\Models\User;

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
}
