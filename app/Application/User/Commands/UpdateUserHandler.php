<?php

declare(strict_types=1);

namespace App\Application\User\Commands;

use App\Application\User\Support\PlanRoleAssignments;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;

final class UpdateUserHandler
{
    public function __construct(
        private readonly UserWriteRepositoryInterface $users,
        private readonly PlanRoleAssignments $planRoles,
    ) {}

    public function handle(UpdateUser $command, WriteScope $scope): void
    {
        $planBefore = $this->planRoles->planRoles($command->owner);
        $this->users->updateAccount($scope, $command->data);
        $this->users->syncRolesAndPermissions(
            $command->owner,
            $command->roleIds,
            $command->permissionIds,
        );
        $this->planRoles->recordIfChanged($command->owner, $planBefore);
    }
}
