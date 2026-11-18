<?php

declare(strict_types=1);

// Stands for database/: seeders set roles directly, outside the checked directories
namespace Database\Seeders\RolesWritten;

use App\Models\User;

final class SeedsRoles
{
    public function run(User $user): void
    {
        $user->assignRole('admin');
    }
}
