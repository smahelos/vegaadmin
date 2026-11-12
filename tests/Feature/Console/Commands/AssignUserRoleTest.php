<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Infrastructure\Authorization\PermissionCatalog;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AssignUserRoleTest extends TestCase
{
    #[Test]
    public function the_operator_gives_an_account_the_admin_role(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();

        $command = $this->pendingCommand(
            'user:assign-role',
            ['email' => $user->email, 'role' => PermissionCatalog::ADMIN_ROLE],
        );
        $command->assertExitCode(0);
        $command->run();

        $this->assertTrue(
            $user->hasRole(PermissionCatalog::ADMIN_ROLE, 'backpack'),
        );
    }

    #[Test]
    public function only_a_plan_role_change_restarts_the_quota_window(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();

        $command = $this->pendingCommand(
            'user:assign-role',
            ['email' => $user->email, 'role' => 'backend_user'],
        );
        $command->assertExitCode(0);
        $command->run();
        $user->refresh();
        $this->assertNull($user->plan_role_assigned_at);

        $command = $this->pendingCommand(
            'user:assign-role',
            ['email' => $user->email, 'role' => 'frontend_user_plus'],
        );
        $command->assertExitCode(0);
        $command->run();
        $user->refresh();
        $this->assertNotNull($user->plan_role_assigned_at);
    }

    #[Test]
    public function an_unknown_account_is_reported(): void
    {
        $command = $this->pendingCommand(
            'user:assign-role',
            ['email' => 'missing-' . uniqid() . '@billex.test', 'role' => PermissionCatalog::ADMIN_ROLE],
        );
        $command->expectsOutputToContain('User not found');
        $command->assertExitCode(1);
        $command->run();
    }
}
