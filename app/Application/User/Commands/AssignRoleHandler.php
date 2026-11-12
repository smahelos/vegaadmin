<?php

declare(strict_types=1);

namespace App\Application\User\Commands;

use App\Application\User\Support\PlanRoleAssignments;
use App\Domain\Shared\Authorization\ValueObjects\AllOwnersScope;
use App\Domain\User\Contracts\UserReadRepositoryInterface;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;
use App\Domain\User\Exceptions\RoleNotFoundException;
use App\Domain\User\Exceptions\UserNotFoundException;

final class AssignRoleHandler
{
    public function __construct(
        private readonly UserReadRepositoryInterface $userReads,
        private readonly UserWriteRepositoryInterface $users,
        private readonly PlanRoleAssignments $planRoles,
    ) {}

    /**
     * @throws RoleNotFoundException
     * @throws UserNotFoundException
     */
    public function handle(AssignRole $command, AllOwnersScope $scope): void
    {
        $userId = $this->userReads->findIdByEmail($scope, $command->email)
            ?? throw new UserNotFoundException($command->email);
        $planBefore = $this->planRoles->planRoles($userId);
        $this->users->assignRole($userId, $command->role);
        $this->planRoles->recordIfChanged($userId, $planBefore);
    }
}
