<?php

declare(strict_types=1);

namespace App\Application\User\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\User\ValueObjects\UserPassword;

/**
 * @implements CommandInterface<null>
 */
final readonly class ChangePassword extends OwnedUseCase implements CommandInterface
{
    public function __construct(
        UserId $owner,
        public UserPassword $password,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::PROFILE_UPDATE);
    }
}
