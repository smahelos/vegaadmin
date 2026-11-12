<?php

declare(strict_types=1);

namespace App\Application\User\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Domain\User\DTO\UserDTO;
use App\Domain\User\DTO\UserWriteData;
use App\Domain\User\Enums\Permission;

/**
 * An account created by an admin, with the roles and direct permissions the user form selects.
 *
 * @implements CommandInterface<UserDTO>
 */
final readonly class CreateUser implements CommandInterface
{
    /**
     * @param  list<int>  $roleIds
     * @param  list<int>  $permissionIds
     */
    public function __construct(
        public UserWriteData $data,
        public array $roleIds,
        public array $permissionIds,
    ) {}

    public static function access(): Access
    {
        return Access::permission(Permission::USER_CREATE);
    }
}
