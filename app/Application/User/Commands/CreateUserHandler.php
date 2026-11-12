<?php

declare(strict_types=1);

namespace App\Application\User\Commands;

use App\Application\User\Support\PlanRoleAssignments;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;
use App\Domain\User\DTO\UserDTO;
use App\Domain\User\ValueObjects\UserId;

final class CreateUserHandler
{
    public function __construct(
        private readonly UserWriteRepositoryInterface $users,
        private readonly PlanRoleAssignments $planRoles,
    ) {}

    public function handle(CreateUser $command): UserDTO
    {
        $created = $this->users->newAccount($command->data);
        $userId = UserId::fromInt($created->id);
        $this->users->syncRolesAndPermissions(
            $userId,
            $command->roleIds,
            $command->permissionIds,
        );
        $this->planRoles->recordIfChanged($userId, []);

        return $created;
    }
}
