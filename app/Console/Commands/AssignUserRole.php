<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Application\User\Commands\AssignRole;
use App\Domain\User\Exceptions\UserNotFoundException;
use Illuminate\Console\Command;

/**
 * Gives the first admin the admin role, before any admin can do it in the admin panel (README, Installation).
 */
final class AssignUserRole extends Command
{
    protected $signature = 'user:assign-role
                            {email : Email of the account}
                            {role : Role name}';

    protected $description = 'Assign a role to a user account';

    public function handle(AuthorizingUseCaseRunnerInterface $useCases): int
    {
        $email = (string) $this->argument('email');
        $role = (string) $this->argument('role');

        try {
            $useCases->run(
                Actor::system(),
                new AssignRole($email, $role),
            );
        } catch (UserNotFoundException) {
            $this->error('User not found: ' . $email);

            return self::FAILURE;
        }
        $this->info('Role ' . $role . ' assigned to ' . $email);

        return self::SUCCESS;
    }
}
