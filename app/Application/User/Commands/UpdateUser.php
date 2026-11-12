<?php

declare(strict_types=1);

namespace App\Application\User\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\User\DTO\UserWriteData;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * An admin's change of an account, its roles and direct permissions; the owner is the account itself.
 *
 * @implements CommandInterface<null>
 */
final readonly class UpdateUser extends OwnedUseCase implements CommandInterface
{
    /**
     * @param  list<int>  $roleIds
     * @param  list<int>  $permissionIds
     */
    public function __construct(
        UserId $owner,
        public UserWriteData $data,
        public array $roleIds,
        public array $permissionIds,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::USER_UPDATE);
    }
}
