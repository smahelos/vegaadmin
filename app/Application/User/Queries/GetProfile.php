<?php

declare(strict_types=1);

namespace App\Application\User\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\User\DTO\UserDTO;
use App\Domain\User\Enums\Permission;

/**
 * The profile form of the signed-in user.
 *
 * @implements QueryInterface<UserDTO>
 */
final readonly class GetProfile extends OwnedUseCase implements QueryInterface
{
    public static function access(): Access
    {
        return Access::permission(Permission::PROFILE_UPDATE);
    }
}
