<?php

declare(strict_types=1);

namespace App\Domain\User\Contracts;

use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\User\DTO\UserDTO;
use App\Domain\User\DTO\UserWriteData;
use App\Domain\User\Exceptions\RoleNotFoundException;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\User\ValueObjects\UserPassword;
use DateTimeImmutable;

interface UserWriteRepositoryInterface
{
    /**
     * A new account, from registration or the admin; it has no owner before it exists, so it takes no scope.
     */
    public function newAccount(UserWriteData $data): UserDTO;

    /**
     * The name and email, and the password when the data carries one.
     */
    public function updateAccount(WriteScope $scope, UserWriteData $data): void;

    public function changePassword(WriteScope $scope, UserPassword $password): void;

    /**
     * Locks the user's row until the surrounding transaction ends, so that checks of the user's capacity and the
     * writes they allow run one request at a time.
     */
    public function lockForUpdate(UserId $id): void;

    /**
     * @throws RoleNotFoundException
     */
    public function assignRole(UserId $id, string $role): void;

    /**
     * Replaces the user's roles and direct permissions, as the admin's user form selects them.
     *
     * @param  list<int>  $roleIds
     * @param  list<int>  $permissionIds
     */
    public function syncRolesAndPermissions(
        UserId $id,
        array $roleIds,
        array $permissionIds,
    ): void;

    /**
     * Starts the monthly quota window of the user's new plan role.
     */
    public function recordPlanRoleAssignment(UserId $id, DateTimeImmutable $assigned): void;
}
